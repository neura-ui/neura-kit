/**
 * The side panel's folder tree, worked out from the nodes: the rows to show (folders
 * only, children under the expanded ones) and the path down to a folder. No DOM and no
 * local import, so it runs in Node.
 */

export type TreeNode = { id: string | number; name: string; type?: string; children?: TreeNode[] };

export type TreeRow = { id: string; name: string; depth: number; hasChildren: boolean; expanded: boolean; path: string[] };

const isFolder = (node: TreeNode): boolean => node.type === 'folder' || Array.isArray(node.children);

/** Folder rows in the order of the nodes, the children of expanded folders under them. */
export function folderRows(nodes: TreeNode[], expanded: Set<string>, path: string[] = []): TreeRow[] {
  const rows: TreeRow[] = [];

  for (const node of nodes) {
    if (!isFolder(node)) continue;

    const id = String(node.id);
    const children = (node.children ?? []).filter(isFolder);
    const open = expanded.has(id);
    const here = [...path, id];

    rows.push({ id, name: node.name, depth: path.length, hasChildren: children.length > 0, expanded: open, path: here });
    if (open && children.length) rows.push(...folderRows(children, expanded, here));
  }

  return rows;
}

/** The ids of the folders from the top down to `id`, itself included; null when it is nowhere. */
export function pathTo(nodes: TreeNode[], id: string, path: string[] = []): string[] | null {
  for (const node of nodes) {
    if (!isFolder(node)) continue;

    const here = [...path, String(node.id)];
    if (String(node.id) === id) return here;

    const found = pathTo(node.children ?? [], id, here);
    if (found) return found;
  }

  return null;
}

/** Every folder's id, top down: the "Move to" list shows them all, unfolded. */
export function allFolderIds(nodes: TreeNode[]): string[] {
  return nodes.filter(isFolder).flatMap((node) => [String(node.id), ...allFolderIds(node.children ?? [])]);
}
