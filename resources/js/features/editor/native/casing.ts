/**
 * Text case over the selection. Only text node values change, so every mark
 * (bold, links, colours) stays where it was.
 */

import { closestBlock, textNodesInRange } from './dom';
import { changeCase, type CaseMode } from './textcase';

export function applyCase(root: HTMLElement, range: Range, mode: CaseMode): void {
  if (range.collapsed) return;
  const nodes = textNodesInRange(range, root);
  if (!nodes.length) return;

  let block: HTMLElement | null = null;
  let before = '';

  for (const node of nodes) {
    const owner = closestBlock(node, root);
    if (owner !== block) {
      block = owner;
      // What precedes the selection in its block decides word and sentence starts.
      const probe = root.ownerDocument!.createRange();
      if (owner) probe.selectNodeContents(owner);
      probe.setEnd(node, 0);
      before = owner ? probe.toString() : '';
    }
    const value = node.nodeValue ?? '';
    const next = changeCase(value, mode, before.slice(-40));
    if (next !== value) node.nodeValue = next;
    before += next;
  }

  const last = nodes[nodes.length - 1];
  range.setStart(nodes[0], 0);
  range.setEnd(last, last.length);
}
