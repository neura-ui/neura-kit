/**
 * The upload panel's list: each file sent gets an id, and what the app reports about it
 * (progress, sent, failed) — or what the person does (cancel, retry) — moves it along.
 * No DOM and no local import, so it runs in Node.
 */

export type UploadStatus = 'waiting' | 'sending' | 'done' | 'failed' | 'cancelled';

export type Upload = { id: string; name: string; size: number; status: UploadStatus; progress: number; message?: string };

export type UploadEvent = {
  type: 'progress' | 'uploaded' | 'failed' | 'cancel' | 'retry';
  id: string;
  progress?: number;
  message?: string;
};

export function queueFiles(files: Array<{ name: string; size: number }>, makeId: () => string): Upload[] {
  return files.map((file) => ({ id: makeId(), name: file.name, size: file.size, status: 'waiting', progress: 0 }));
}

/** The list after one event; the same list when the id is not in it. */
export function applyUploadEvent(list: Upload[], event: UploadEvent): Upload[] {
  const index = list.findIndex((upload) => upload.id === event.id);
  if (index === -1) return list;

  const upload = list[index];
  let next: Upload = upload;

  switch (event.type) {
    case 'progress':
      if (upload.status !== 'waiting' && upload.status !== 'sending') return list;
      next = { ...upload, status: 'sending', progress: Math.max(0, Math.min(100, Math.round(Number(event.progress) || 0))) };
      break;
    case 'uploaded':
      next = { ...upload, status: 'done', progress: 100, message: undefined };
      break;
    case 'failed':
      next = { ...upload, status: 'failed', message: event.message };
      break;
    case 'cancel':
      if (upload.status === 'done') return list;
      next = { ...upload, status: 'cancelled' };
      break;
    case 'retry':
      if (upload.status !== 'failed' && upload.status !== 'cancelled') return list;
      next = { ...upload, status: 'waiting', progress: 0, message: undefined };
      break;
  }

  return list.map((other, at) => (at === index ? next : other));
}

/** Files still going (waiting or sending), sent, and failed. */
export function uploadSummary(list: Upload[]): { active: number; done: number; failed: number } {
  return {
    active: list.filter((upload) => upload.status === 'waiting' || upload.status === 'sending').length,
    done: list.filter((upload) => upload.status === 'done').length,
    failed: list.filter((upload) => upload.status === 'failed').length,
  };
}
