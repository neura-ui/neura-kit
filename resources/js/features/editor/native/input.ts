/**
 * Keyboard and clipboard handling.
 *
 * Almost everything here exists because the browser's default contenteditable
 * behaviour is wrong once the document is paginated. Enter at the bottom of a
 * sheet, Backspace at the top of one — left to itself the browser will happily
 * drag a page host into another page host, or delete one outright. So the
 * structural edits are intercepted and performed against the *logical*
 * document, and the flow engine puts the pages right afterwards.
 */

import {
  blocksInRange,
  closestBlock,
  emptyParagraph,
  isBlock,
  isEmptyBlock,
  tag,
} from './dom';
import { toggleMark } from './marks';
import * as blocks from './blocks';
import * as images from './images';
import * as review from './review';
import { blockId, currentRange, setCaret } from './selection';
import { sanitizeHtml, textToFragment, isSafeUrl } from './sanitize';
import type { BlockName } from './schema';

export interface InputContext {
  root: HTMLElement;
  /** Called after a structural edit so the host can reflow and record history. */
  changed(kind?: 'typing' | 'command'): void;
  /** Ask the host to prompt for a link target. */
  promptLink(): void;
  undo(): void;
  redo(): void;
  /**
   * In Suggesting mode, who is suggesting: a new paragraph or two joined
   * become suggestions instead of happening at once.
   */
  suggesting?: { author: string } | null;
}

/** The leaf block before `block` in document order, across page hosts. */
function previousBlock(block: HTMLElement, root: HTMLElement): HTMLElement | null {
  const walker = root.ownerDocument!.createTreeWalker(root, NodeFilter.SHOW_ELEMENT, {
    acceptNode: (node) => (isBlock(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP),
  });

  let previous: HTMLElement | null = null;
  let node = walker.nextNode();
  while (node) {
    if (node === block) return previous;
    previous = node as HTMLElement;
    node = walker.nextNode();
  }
  return null;
}

/** The leaf block after `block` in document order, across page hosts. */
function nextBlock(block: HTMLElement, root: HTMLElement): HTMLElement | null {
  const walker = root.ownerDocument!.createTreeWalker(root, NodeFilter.SHOW_ELEMENT, {
    acceptNode: (node) => (isBlock(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP),
  });

  let found = false;
  let node = walker.nextNode();
  while (node) {
    if (found) return node as HTMLElement;
    if (node === block) found = true;
    node = walker.nextNode();
  }
  return null;
}

/** Character offset of the caret inside its block. */
function offsetInBlock(block: HTMLElement, range: Range): number {
  const probe = block.ownerDocument!.createRange();
  probe.selectNodeContents(block);
  try {
    probe.setEnd(range.startContainer, range.startOffset);
  } catch {
    return 0;
  }
  return probe.toString().length;
}

/** Remove a block along with any container it just emptied. */
function removeBlock(block: HTMLElement): void {
  const parent = block.parentElement;
  block.remove();

  if (parent && ['ul', 'ol', 'tbody', 'table'].includes(tag(parent)) && !parent.firstElementChild) {
    removeBlock(parent);
  }
}

/** Split the block at the caret, returning the new block that follows it. */
function splitBlockAtCaret(root: HTMLElement, range: Range): HTMLElement | null {
  const block = closestBlock(range.startContainer, root);
  if (!block || !block.parentElement) return null;

  const doc = root.ownerDocument!;
  const atEnd = offsetInBlock(block, range) >= (block.textContent ?? '').length;

  const tail = doc.createRange();
  tail.setStart(range.startContainer, range.startOffset);
  tail.setEnd(block, block.childNodes.length);
  const rest = tail.extractContents();

  const next = block.cloneNode(false) as HTMLElement;
  next.removeAttribute('data-nk-id');
  next.removeAttribute('data-nk-part');
  // A suggested join or new paragraph belongs to where the block started, not to its new half.
  next.removeAttribute('data-suggest-join');
  next.removeAttribute('data-suggest-split');
  // A new checklist item starts unticked.
  if (next.hasAttribute('data-checked')) next.setAttribute('data-checked', 'false');
  next.appendChild(rest);

  if (!next.textContent?.length && !next.querySelector('img, br')) {
    next.appendChild(doc.createElement('br'));
  }
  if (!block.textContent?.length && !block.querySelector('img, br')) {
    block.appendChild(doc.createElement('br'));
  }

  block.parentElement.insertBefore(next, block.nextSibling);
  blockId(next);

  // Enter at the end of a heading starts ordinary text, as in Docs.
  const style = blocks.blockNameOf(next);
  if (atEnd && style && style !== 'paragraph' && tag(next) !== 'li') {
    const paragraph = emptyParagraph(doc);
    paragraph.setAttribute('data-nk-id', next.getAttribute('data-nk-id')!);
    next.replaceWith(paragraph);
    return paragraph;
  }

  return next;
}

/** A new paragraph at the caret, as Enter makes one (voice typing's "new paragraph"). */
export function breakParagraph(root: HTMLElement, range: Range): void {
  const next = splitBlockAtCaret(root, range);
  if (next) setCaret(root, next, 0);
}

/** Enter: split the block, or lift out of an empty list item. */
function handleEnter(context: InputContext, range: Range): boolean {
  const { root } = context;
  const block = closestBlock(range.startContainer, root);
  if (!block) return false;

  if (!range.collapsed) {
    range.deleteContents();
    range.collapse(true);
  }

  // A cell is one block of its own: Enter starts a new line in it rather than
  // splitting it into two cells side by side.
  if (tag(block) === 'td' || tag(block) === 'th') {
    insertLineBreak(range);
    context.changed();
    return true;
  }

  // Enter in a caption, or beside an image, starts a paragraph below the figure.
  const figure = block.closest('figure');
  if (figure && figure.querySelector('img')) {
    setCaret(root, images.paragraphAfter(figure as HTMLElement), 0);
    context.changed();
    return true;
  }

  // Enter on an empty list item leaves the list instead of adding a bullet.
  if (tag(block) === 'li' && isEmptyBlock(block)) {
    blocks.outdent(root, range);
    context.changed();
    return true;
  }

  const next = splitBlockAtCaret(root, range);
  if (!next) return false;
  if (context.suggesting) review.suggestSplit(next, context.suggesting.author);

  setCaret(root, next, 0);
  context.changed();
  return true;
}

/** A `<br>` at the caret, with the second one a line needs when it ends the block. */
export function insertLineBreak(range: Range): void {
  const doc = range.startContainer.ownerDocument!;
  const lineBreak = doc.createElement('br');
  range.insertNode(lineBreak);

  const block = lineBreak.parentElement;
  let after = lineBreak.nextSibling;
  while (after && after.nodeType === Node.TEXT_NODE && !after.nodeValue) after = after.nextSibling;
  if (!after && block) block.appendChild(doc.createElement('br'));

  const caret = doc.createRange();
  caret.setStartAfter(lineBreak);
  caret.collapse(true);
  const selection = doc.defaultView?.getSelection();
  selection?.removeAllRanges();
  selection?.addRange(caret);
}

/** Backspace at the very start of a block merges it into the previous one. */
function handleBackspaceAtStart(context: InputContext, range: Range): boolean {
  const { root } = context;
  const block = closestBlock(range.startContainer, root);
  if (!block) return false;

  // An indented or listed block first steps back out, like Docs.
  if (tag(block) === 'li' || blocks.indentOf(block) > 0) {
    blocks.outdent(root, range);
    context.changed();
    return true;
  }

  // The caret may be at the top of a page only because the flow engine cut this
  // paragraph in half there. Logically it is mid-sentence, so Backspace must
  // delete the character before it rather than "merge" a paragraph with itself.
  const partOf = block.getAttribute('data-nk-part');
  const previousPart = block.previousElementSibling ?? previousBlock(block, root);
  if (
    partOf &&
    Number(partOf) > 0 &&
    previousPart &&
    previousPart.getAttribute('data-nk-id') === block.getAttribute('data-nk-id')
  ) {
    const text = previousPart.textContent ?? '';
    if (text.length > 0) {
      setCaret(root, previousPart as HTMLElement, text.length - 1);
      const trimmed = currentRange(root);
      if (trimmed) {
        trimmed.setEnd(trimmed.startContainer, Math.min(
          (trimmed.startContainer.textContent ?? '').length,
          trimmed.startOffset + 1
        ));
        trimmed.deleteContents();
      }
      context.changed();
      return true;
    }
  }

  // A rule, table or image sitting directly above is deleted rather than
  // merged into — there is nothing to merge text with.
  const sibling = block.previousElementSibling;
  // At the top of a page a break started, Backspace takes the break away.
  if (sibling && sibling.hasAttribute('data-nk-break')) {
    sibling.remove();
    context.changed();
    return true;
  }
  if (sibling && ['hr', 'table', 'figure'].includes(tag(sibling))) {
    removeBlock(sibling as HTMLElement);
    context.changed();
    return true;
  }

  const previous = previousBlock(block, root);
  if (!previous) {
    // Start of the document: only reset the style.
    if (blocks.blockNameOf(block) !== 'paragraph') {
      blocks.setBlockType(root, range, 'paragraph');
      context.changed();
      return true;
    }
    return true; // swallow, nothing before to merge with
  }

  const previousLength = (previous.textContent ?? '').length;

  if (tag(previous) === 'figure') {
    removeBlock(previous);
    context.changed();
    return true;
  }

  // Suggesting: the two paragraphs are marked to be joined, the caret goes up.
  if (context.suggesting) {
    const joined = review.suggestJoin(root, block, context.suggesting.author);
    setCaret(root, previous, joined ? previousLength : (previous.textContent ?? '').length);
    context.changed();
    return true;
  }

  if (previous.querySelector('br:only-child')) previous.querySelector('br')?.remove();
  while (block.firstChild) {
    const child = block.firstChild;
    if (tag(child) === 'br' && !child.nextSibling) {
      block.removeChild(child);
      break;
    }
    previous.appendChild(child);
  }

  removeBlock(block);
  previous.normalize();
  setCaret(root, previous, previousLength);
  context.changed();
  return true;
}

/** Delete at the very end of a block pulls the next block up into it. */
function handleDeleteAtEnd(context: InputContext, range: Range): boolean {
  const { root } = context;
  const block = closestBlock(range.startContainer, root);
  if (!block) return false;

  const next = nextBlock(block, root);
  if (!next) return true;

  const offset = (block.textContent ?? '').length;

  if (['hr', 'figure', 'table'].includes(tag(next))) {
    removeBlock(next);
    context.changed();
    return true;
  }

  // The next part of this very paragraph (a page cut it) is no paragraph to join.
  if (context.suggesting && next.getAttribute('data-nk-id') !== block.getAttribute('data-nk-id')) {
    review.suggestJoin(root, next, context.suggesting.author);
    setCaret(root, block, offset);
    context.changed();
    return true;
  }

  block.querySelector('br:only-child')?.remove();
  while (next.firstChild) {
    const child = next.firstChild;
    if (tag(child) === 'br' && !child.nextSibling) {
      next.removeChild(child);
      break;
    }
    block.appendChild(child);
  }

  removeBlock(next);
  block.normalize();
  setCaret(root, block, offset);
  context.changed();
  return true;
}

/** A selection covering several blocks is collapsed manually, then merged. */
function deleteAcrossBlocks(context: InputContext, range: Range): boolean {
  const { root } = context;
  const touched = blocksInRange(range, root);
  if (touched.length < 2) return false;

  const first = touched[0];
  const offset = offsetInBlock(first, range);

  range.deleteContents();

  // `deleteContents` leaves both boundary blocks behind; fold the tail back in.
  const last = touched[touched.length - 1];
  if (last.isConnected && last !== first && first.isConnected) {
    while (last.firstChild) first.appendChild(last.firstChild);
    removeBlock(last);
  }

  for (const block of touched.slice(1, -1)) {
    if (block.isConnected && isEmptyBlock(block)) removeBlock(block);
  }

  if (!first.firstChild) first.appendChild(root.ownerDocument!.createElement('br'));
  first.normalize();
  setCaret(root, first, offset);
  context.changed();
  return true;
}

/** Markdown-style shortcuts fired by the space key at the start of a block. */
const AUTOFORMAT: Array<{ pattern: RegExp; apply: (root: HTMLElement, range: Range) => void }> = [
  { pattern: /^#$/, apply: (r, g) => blocks.setBlockType(r, g, 'heading1') },
  { pattern: /^##$/, apply: (r, g) => blocks.setBlockType(r, g, 'heading2') },
  { pattern: /^###$/, apply: (r, g) => blocks.setBlockType(r, g, 'heading3') },
  { pattern: /^####$/, apply: (r, g) => blocks.setBlockType(r, g, 'heading4') },
  { pattern: /^>$/, apply: (r, g) => blocks.setBlockType(r, g, 'blockquote') },
  { pattern: /^[-*+]$/, apply: (r, g) => blocks.toggleList(r, g, 'ul') },
  { pattern: /^1\.$/, apply: (r, g) => blocks.toggleList(r, g, 'ol') },
  { pattern: /^\[\s?\]$/, apply: (r, g) => blocks.toggleChecklist(r, g) },
  { pattern: /^\[[xX]\]$/, apply: (r, g) => blocks.toggleChecklist(r, g, true) },
];

function tryAutoformat(context: InputContext, range: Range): boolean {
  const { root } = context;
  const block = closestBlock(range.startContainer, root);
  if (!block || tag(block) === 'li' || tag(block) === 'pre') return false;

  const offset = offsetInBlock(block, range);
  const prefix = (block.textContent ?? '').slice(0, offset);

  const rule = AUTOFORMAT.find((entry) => entry.pattern.test(prefix));
  if (!rule) return false;

  // Drop the marker characters, then apply the style.
  const doc = root.ownerDocument!;
  const cut = doc.createRange();
  cut.setStart(block, 0);
  cut.setEnd(range.startContainer, range.startOffset);
  cut.deleteContents();

  const fresh = currentRange(root) ?? range;
  rule.apply(root, fresh);
  context.changed();
  return true;
}

const isMod = (event: KeyboardEvent): boolean => event.metaKey || event.ctrlKey;

const HEADING_KEYS: Record<string, BlockName> = {
  '0': 'paragraph',
  '1': 'heading1',
  '2': 'heading2',
  '3': 'heading3',
  '4': 'heading4',
};

/** Central keydown handler. Returns true when the event was consumed. */
export function handleKeydown(event: KeyboardEvent, context: InputContext): boolean {
  const { root } = context;
  const range = currentRange(root);
  if (!range) return false;

  // ---- shortcuts -------------------------------------------------------
  if (isMod(event)) {
    const key = event.key.toLowerCase();

    if (key === 'z' && !event.shiftKey) {
      event.preventDefault();
      context.undo();
      return true;
    }
    if ((key === 'z' && event.shiftKey) || key === 'y') {
      event.preventDefault();
      context.redo();
      return true;
    }
    if (key === 'b' || key === 'i' || key === 'u') {
      event.preventDefault();
      const mark = key === 'b' ? 'bold' : key === 'i' ? 'italic' : 'underline';
      if (context.suggesting && !range.collapsed) {
        review.suggestFormat(root, range, mark, false, () => toggleMark(root, range, mark), context.suggesting.author);
      } else {
        toggleMark(root, range, mark);
      }
      context.changed();
      return true;
    }
    if (key === 'k') {
      event.preventDefault();
      context.promptLink();
      return true;
    }
    if (key === '\\') {
      event.preventDefault();
      blocks.clearBlockFormatting(root, range);
      context.changed();
      return true;
    }
    if (event.altKey && HEADING_KEYS[event.key]) {
      event.preventDefault();
      const apply = () => blocks.setBlockType(root, range, HEADING_KEYS[event.key]);
      if (context.suggesting) review.suggestStyle(root, blocksInRange(range, root), apply, context.suggesting.author);
      else apply();
      context.changed();
      return true;
    }
    if (event.shiftKey && (key === '9' || event.code === 'Digit9')) {
      event.preventDefault();
      blocks.toggleChecklist(root, range);
      context.changed();
      return true;
    }
    if (event.shiftKey && (key === '7' || key === '8')) {
      event.preventDefault();
      blocks.toggleList(root, range, key === '7' ? 'ol' : 'ul');
      context.changed();
      return true;
    }
    if (event.key === 'Enter') {
      event.preventDefault();
      blocks.insertPageBreak(root, range);
      context.changed();
      return true;
    }
  }

  // ---- structural keys -------------------------------------------------
  if (event.key === 'Enter' && !event.shiftKey && !isMod(event)) {
    event.preventDefault();
    return handleEnter(context, range);
  }

  if (event.key === 'Tab') {
    event.preventDefault();

    // Inside a table, Tab walks the cells — and adds a row when it runs off the
    // end — rather than indenting the cell's contents.
    if (blocks.moveToAdjacentCell(root, range, !event.shiftKey)) {
      context.changed();
      return true;
    }

    const apply = () => (event.shiftKey ? blocks.outdent(root, range) : blocks.indent(root, range));
    // A paragraph's indent is part of its look; list items change level, which applies at once.
    const touched = blocksInRange(range, root);
    if (context.suggesting && touched.every((block) => tag(block) !== 'li')) review.suggestStyle(root, touched, apply, context.suggesting.author);
    else apply();
    context.changed();
    return true;
  }

  if (event.key === 'Backspace') {
    if (!range.collapsed) {
      if (deleteAcrossBlocks(context, range)) {
        event.preventDefault();
        return true;
      }
      return false;
    }
    const block = closestBlock(range.startContainer, root);
    if (block && offsetInBlock(block, range) === 0) {
      event.preventDefault();
      return handleBackspaceAtStart(context, range);
    }
    return false;
  }

  if (event.key === 'Delete') {
    if (!range.collapsed) {
      if (deleteAcrossBlocks(context, range)) {
        event.preventDefault();
        return true;
      }
      return false;
    }
    const block = closestBlock(range.startContainer, root);
    if (block && offsetInBlock(block, range) >= (block.textContent ?? '').length) {
      event.preventDefault();
      return handleDeleteAtEnd(context, range);
    }
    return false;
  }

  if (event.key === ' ' && range.collapsed) {
    if (tryAutoformat(context, range)) {
      event.preventDefault();
      return true;
    }
  }

  return false;
}

/** Paste: prefer HTML, sanitize it, fall back to plain text. */
export function handlePaste(event: ClipboardEvent, context: InputContext): void {
  event.preventDefault();

  const { root } = context;
  const range = currentRange(root);
  if (!range) return;

  const data = event.clipboardData;
  if (!data) return;

  const html = data.getData('text/html');
  const text = data.getData('text/plain');

  if (!range.collapsed) {
    if (!deleteAcrossBlocks(context, range)) range.deleteContents();
  }

  const target = currentRange(root) ?? range;

  // A bare URL over a selection becomes a link on that text, as users expect.
  if (!html && text && isSafeUrl(text) && /^https?:\/\//i.test(text.trim())) {
    toggleMark(root, target, 'link', text.trim());
    context.changed();
    return;
  }

  const fragment = html ? sanitizeHtml(html) : textToFragment(text);
  // Comment threads stay with their own document.
  fragment.querySelectorAll('aside[data-comments]').forEach((aside) => aside.remove());
  insertFragment(context, target, fragment);
}

/**
 * Insert cleaned content at the caret.
 *
 * Inline-only content is spliced into the current block; anything containing
 * blocks splits the current block and lands between the halves.
 */
export function insertFragment(
  context: InputContext,
  range: Range,
  fragment: DocumentFragment
): void {
  const { root } = context;
  const block = closestBlock(range.startContainer, root);
  if (!block) return;

  const children = Array.from(fragment.childNodes);
  const hasBlocks = children.some((node) => isBlock(node) || ['ul', 'ol', 'table'].includes(tag(node)));

  if (!hasBlocks) {
    range.insertNode(fragment);
    block.normalize();
    const last = children[children.length - 1];
    if (last) {
      const after = root.ownerDocument!.createRange();
      after.setStartAfter(last);
      after.collapse(true);
      const selection = root.ownerDocument!.defaultView?.getSelection();
      selection?.removeAllRanges();
      selection?.addRange(after);
    }
    context.changed();
    return;
  }

  const tail = splitBlockAtCaret(root, range);
  const anchor = tail ?? block.nextSibling;
  const host = block.parentElement;
  if (!host) return;

  let lastInserted: HTMLElement | null = null;
  for (const node of children) {
    if (node.nodeType === Node.TEXT_NODE && !node.nodeValue?.trim()) continue;

    const element =
      node.nodeType === Node.ELEMENT_NODE
        ? (node as HTMLElement)
        : (() => {
            const paragraph = root.ownerDocument!.createElement('p');
            paragraph.appendChild(node);
            return paragraph;
          })();

    host.insertBefore(element, anchor as Node | null);
    blockId(element);
    lastInserted = element;
  }

  // Drop the empty halves the split left behind.
  if (isEmptyBlock(block) && lastInserted) removeBlock(block);
  if (tail && isEmptyBlock(tail) && lastInserted) removeBlock(tail);

  if (lastInserted) setCaret(root, lastInserted, (lastInserted.textContent ?? '').length);
  context.changed();
}
