/**
 * Editor loader.
 *
 * Imports the editor so its Alpine component registers itself. This used to
 * pull in three variants; Tiptap and Editor.js have been removed, leaving the
 * kit's own dependency-free editor as the only one.
 */


import './variants/native';
