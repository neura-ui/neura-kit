type DropzoneStatus = 'idle' | 'uploading' | 'success' | 'error';

type DropzonePreview = {
    type: 'image' | 'file';
    url?: string;
    name: string;
    size: string;
    extension?: string;
    progress: number;
    status: DropzoneStatus;
    error?: string | null;
    uuid: string;
};

export type DropzoneOptions = {
    accept?: string;
    maxSizeBytes?: number;
    multiple?: boolean;
    chunkSize?: number;
    uploadUrl?: string | null;
    uploadHeaders?: Record<string, string>;
    name?: string | null;
};

const defaultOptions: Required<DropzoneOptions> = {
    accept: 'image/*',
    maxSizeBytes: 10 * 1024 * 1024,
    multiple: false,
    chunkSize: 1 * 1024 * 1024,
    uploadUrl: null,
    uploadHeaders: {},
    name: null,
};

function uid() {
    if (crypto.randomUUID) {
        return crypto.randomUUID();
    }
    
    // Fallback pour générer un UUID v4 valide
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function formatFileSize(bytes: number) {
    if (!bytes) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`;
}

/**
 * Extract a human-readable error message from HTTP response
 */
function extractErrorMessage(responseText: string, statusCode: number): string {
    // Try to parse as JSON first
    try {
        const json = JSON.parse(responseText);
        if (json.message) return json.message;
        if (json.error) return json.error;
        if (json.errors && typeof json.errors === 'object') {
            const firstError = Object.values(json.errors)[0];
            if (Array.isArray(firstError)) return firstError[0];
            return String(firstError);
        }
    } catch {
        // Not JSON, continue
    }

    // Check if it's HTML
    if (responseText.trim().startsWith('<')) {
        // Try to extract title from HTML
        const titleMatch = responseText.match(/<title>(.*?)<\/title>/i);
        if (titleMatch && titleMatch[1]) {
            return titleMatch[1].trim();
        }

        // Try to extract h1 from HTML
        const h1Match = responseText.match(/<h1[^>]*>(.*?)<\/h1>/i);
        if (h1Match && h1Match[1]) {
            // Remove HTML tags from h1 content
            return h1Match[1].replace(/<[^>]*>/g, '').trim();
        }

        // Common HTTP status code messages
        const statusMessages: Record<number, string> = {
            400: 'Requête invalide',
            401: 'Non autorisé',
            403: 'Accès interdit',
            404: 'Ressource non trouvée',
            413: 'Fichier trop volumineux - La taille du fichier dépasse la limite autorisée',
            422: 'Données invalides',
            429: 'Trop de requêtes',
            500: 'Erreur serveur',
            502: 'Passerelle invalide',
            503: 'Service indisponible',
            504: 'Délai d\'attente dépassé',
        };

        if (statusMessages[statusCode]) {
            return statusMessages[statusCode];
        }

        return `Erreur HTTP ${statusCode}`;
    }

    // If it's plain text and not too long, use it
    if (responseText.length < 200) {
        return responseText;
    }

    // Fallback
    return `Erreur d'upload (${statusCode})`;
}

async function uploadInChunks(
    file: File,
    {
        chunkSize,
        uploadUrl,
        headers,
        name,
        onProgress,
    }: {
        chunkSize: number;
        uploadUrl: string;
        headers: Record<string, string>;
        name?: string | null;
        onProgress: (percent: number) => void;
    },
): Promise<any> {
    const totalChunks = Math.ceil(file.size / chunkSize);
    const fileUuid = uid();
    let lastResponse: any = null;

    for (let index = 0; index < totalChunks; index++) {
        const start = index * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const blob = file.slice(start, end);

        const formData = new FormData();
        formData.append('chunk', blob, file.name);
        formData.append('fileName', file.name);
        formData.append('fileSize', String(file.size));
        formData.append('chunkIndex', String(index));
        formData.append('totalChunks', String(totalChunks));
        formData.append('uuid', fileUuid);
        if (name) formData.append('field', name);

        // Utilise XHR pour avoir une progression réelle (fetch ne donne pas d'upload progress)
        await new Promise<void>((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);

            Object.entries(headers || {}).forEach(([k, v]) => {
                try {
                    xhr.setRequestHeader(k, v);
                } catch {
                    // ignore invalid headers for XHR
                }
            });

            xhr.upload.onprogress = (evt) => {
                if (!evt.lengthComputable) return;
                const chunkPercent = evt.total > 0 ? evt.loaded / evt.total : 0;
                const overall = ((index + chunkPercent) / totalChunks) * 100;
                onProgress(Math.round(overall));
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // chunk terminé
                    const overall = ((index + 1) / totalChunks) * 100;
                    onProgress(Math.round(overall));
                    
                    // Stocker la réponse (le dernier chunk contient les métadonnées)
                    try {
                        lastResponse = JSON.parse(xhr.responseText);
                    } catch {
                        lastResponse = { success: true };
                    }
                    
                    resolve();
                    return;
                }
                
                // Extract readable error message
                const errorMessage = extractErrorMessage(xhr.responseText, xhr.status);
                reject(new Error(errorMessage));
            };

            xhr.onerror = () => reject(new Error('Erreur réseau - Impossible de se connecter au serveur'));
            xhr.onabort = () => reject(new Error('Upload annulé'));

            xhr.send(formData);
        });
    }
    
    return lastResponse;
}

export function neuraDropzone(options: DropzoneOptions = {}) {
    const config = { ...defaultOptions, ...options };

    return {
        files: [] as File[],
        previews: [] as DropzonePreview[],
        isDragging: false,
        accept: config.accept,
        maxSize: config.maxSizeBytes,
        multiple: config.multiple,
        chunkSize: config.chunkSize,
        uploadUrl: config.uploadUrl,
        uploadHeaders: config.uploadHeaders,
        fieldName: config.name,

        init() {
            // Préparer l'état initial
            this.previews = [];
        },

        triggerFileInput() {
            const input = (this as any).$refs?.fileInput as HTMLInputElement | undefined;
            input?.click();
        },

        handleDragOver(e: DragEvent) {
            e.preventDefault();
            this.isDragging = true;
        },

        handleDragLeave(e: DragEvent) {
            e.preventDefault();
            this.isDragging = false;
        },

        handleDrop(e: DragEvent) {
            e.preventDefault();
            this.isDragging = false;
            const droppedFiles = Array.from(e.dataTransfer?.files || []);
            this.processFiles(droppedFiles);
        },

        handleFileSelect(e: Event) {
            const target = e.target as HTMLInputElement;
            const selectedFiles = Array.from(target.files || []);
            this.processFiles(selectedFiles);
        },

        processFiles(fileList: File[]) {
            const dispatch = (this as any).$dispatch as ((name: string, detail?: any) => void) | undefined;
            const validFiles = fileList.filter((file) => {
                if (file.size > this.maxSize) {
                    dispatch?.('notify', {
                        type: 'error',
                        content:
                            window.t?.('fileExceedsMaxSize', {
                                fileName: file.name,
                                maxSize: Math.round(this.maxSize / 1024 / 1024).toString(),
                            }) ?? `Le fichier ${file.name} dépasse la taille max`,
                        duration: 5000,
                    });
                    return false;
                }
                if (this.accept && this.accept !== '*/*') {
                    const accepts = this.accept.split(',').map((v) => v.trim());
                    const ok = accepts.some((rule) => {
                        if (rule === '*/*') return true;
                        if (rule.endsWith('/*')) {
                            return file.type.startsWith(rule.replace('/*', '/'));
                        }
                        return file.type === rule || file.name.toLowerCase().endsWith(rule.toLowerCase());
                    });
                    if (!ok) return false;
                }
                return true;
            });

            this.files = this.multiple ? [...this.files, ...validFiles] : validFiles.slice(0, 1);
            this.generatePreviews();
            this.startUploads();
        },

        generatePreviews() {
            const hasUpload = Boolean(this.uploadUrl);

            this.previews = this.files.map((file) => ({
                type: file.type.startsWith('image/') ? 'image' : 'file',
                url: undefined,
                name: file.name,
                size: formatFileSize(file.size),
                extension: file.name.split('.').pop()?.toUpperCase(),
                progress: hasUpload ? 0 : 100,
                status: hasUpload ? ('idle' as DropzoneStatus) : ('success' as DropzoneStatus),
                uuid: uid(),
                error: null,
            }));

            this.files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        // Force Alpine reactivity by recreating the array
                        const newPreviews = [...this.previews];
                        newPreviews[index] = { 
                            ...newPreviews[index], 
                            url: (e.target as FileReader).result as string 
                        };
                        this.previews = newPreviews;
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        async startUploads() {
            if (!this.uploadUrl) return;

            for (let index = 0; index < this.files.length; index++) {
                const file = this.files[index];
                if (!file) continue;
                await this.uploadFile(index, file);
            }
        },

        async uploadFile(index: number, file: File) {
            if (!this.uploadUrl) return;
            const dispatch = (this as any).$dispatch as ((name: string, detail?: any) => void) | undefined;
            this.setStatus(index, 'uploading');

            try {
                const response = await uploadInChunks(file, {
                    chunkSize: this.chunkSize,
                    uploadUrl: this.uploadUrl,
                    headers: this.uploadHeaders,
                    name: this.fieldName,
                    onProgress: (percent) => this.setProgress(index, percent),
                });

                this.setProgress(index, 100);
                this.setStatus(index, 'success');
                
                dispatch?.('upload:success', { 
                    file, 
                    index,
                    uuid: this.previews[index]?.uuid,
                    data: response?.data, // Inclut uuid, filename, path, size, mime du backend
                });
            } catch (error: any) {
                this.setStatus(index, 'error', error?.message || 'Échec de l\'upload');
                dispatch?.('upload:error', { file, index, error });
            } finally {
                const currentPreview = this.previews[index];
                if (currentPreview) {
                    // Only set to 100% and success if still uploading (not already error)
                    if (currentPreview.status === 'uploading') {
                        this.setProgress(index, 100);
                        this.setStatus(index, 'success');
                    }
                }
            }
        },

        setProgress(index: number, value: number) {
            if (this.previews[index]) {
                // Force Alpine reactivity by recreating the array
                const newPreviews = [...this.previews];
                newPreviews[index] = { 
                    ...newPreviews[index], 
                    progress: Math.min(100, Math.max(0, value)) 
                };
                this.previews = newPreviews;
            }
        },

        setStatus(index: number, status: DropzoneStatus, error?: string | null) {
            if (this.previews[index]) {
                // Force Alpine reactivity by recreating the array
                const newPreviews = [...this.previews];
                newPreviews[index] = { 
                    ...newPreviews[index], 
                    status, 
                    error: error ?? newPreviews[index].error 
                };
                this.previews = newPreviews;
            }
        },

        removeFile(index: number) {
            this.files.splice(index, 1);
            this.previews.splice(index, 1);
            const input = (this as any).$refs?.fileInput as HTMLInputElement | undefined;
            if (input) input.value = '';
        },
    };
}

if (typeof window !== 'undefined') {
    (window as any).neuraDropzone = neuraDropzone;
}

// Type déjà déclaré dans globals/types.ts, pas besoin de re-déclarer ici