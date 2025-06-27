class DocumentUploader {
    constructor(eleveId) {
        this.eleveId = eleveId;
        this.documentTypes = {};
        this.init();
    }

    async init() {
        try {
            await this.loadDocumentsStatus();
            this.renderDocuments();
            this.updateNextButton();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation:', error);
            this.showGlobalError('Erreur lors du chargement des documents');
        }
    }

    async loadDocumentsStatus() {
        try {
            const response = await fetch(`/documents-status/${this.eleveId}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            if (data.error) {
                throw new Error(data.error);
            }

            this.documentTypes = data.documents;
            this.missingRequired = data.missing_required;
            this.allRequiredUploaded = data.all_required_uploaded;

        } catch (error) {
            console.error('Erreur lors du chargement du statut:', error);
            throw error;
        }
    }

    renderDocuments() {
        const container = document.getElementById('documents-container');
        if (!container) return;
        container.innerHTML = '';

        Object.entries(this.documentTypes).forEach(([type, config]) => {
            const item = this.createDocumentItem(type, config);
            container.appendChild(item);
        });
    }

    createDocumentItem(type, config) {
        const div = document.createElement('div');
        div.className = `fr-card fr-card--shadowed fr-mb-3w p-3 ${config.required ? 'required' : ''} ${config.uploaded ? 'uploaded' : ''}`;

        const label = config.label || type;
        const extension = config.web_path?.split('.').pop().toLowerCase();
        const icon = this.getFileIcon(extension || 'pdf');

        let html = `
            <div class="fr-card__body">
                <div class="fr-card__content">
                    <h4 class="fr-card__title">${label} 
                        <span class="fr-badge ${config.required ? 'fr-badge--error' : 'fr-badge--info'}">
                            ${config.required ? 'Obligatoire' : 'Optionnel'}
                        </span>
                    </h4>
                    ${config.uploaded ? this.createPreviewHtml(type, config, icon) : this.createUploadZoneHtml(type)}
                    <div id="message-${type}" class="fr-mt-2w"></div>
                </div>
            </div>
        `;
        div.innerHTML = html;
        this.attachEventListeners(div, type);
        return div;
    }

    createUploadZoneHtml(type) {
        return `
            <div class="upload-zone fr-mt-2w" data-type="${type}">
                <input type="file" id="file-${type}" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                <div class="fr-upload-group fr-mb-2w fr-border fr-p-2w fr-text--center fr-background-alt--grey">
                    <p><strong>Cliquez pour sélectionner un fichier</strong></p>
                    <p class="fr-text--sm">ou glissez-déposez ici</p>
                    <div class="fr-text--xs">Formats acceptés : PDF, JPG, PNG — 5MB max</div>
                </div>
                <div class="fr-progress fr-mt-2w" style="display:none;">
                    <div class="fr-progress__bar" role="progressbar" style="width:0%"></div>
                </div>
            </div>
        `;
    }

    createPreviewHtml(type, config, icon) {
        return `
            <div class="fr-mt-2w">
                <p>${icon} <strong>Fichier existant :</strong> ${config.web_path.split('/').pop()}</p>
                <div class="fr-btns-group fr-btns-group--sm">
                    <a href="/download-document/${this.eleveId}/${type}" class="fr-btn fr-btn--secondary fr-btn--sm" target="_blank">
                        Télécharger
                    </a>
                    <button type="button" class="fr-btn fr-btn--tertiary fr-btn--sm" onclick="documentUploader.replaceDocument('${type}')">
                        Remplacer
                    </button>
                    <button type="button" class="fr-btn fr-btn--tertiary fr-btn--sm fr-btn--icon-left fr-icon-delete-line" onclick="documentUploader.deleteDocument('${type}')">
                        Supprimer
                    </button>
                </div>
            </div>
        `;
    }

    getFileIcon(extension) {
        const icons = {
            pdf: '<span class="fr-icon-file-pdf-line fr-icon--sm text-danger" aria-hidden="true"></span>',
            jpg: '<span class="fr-icon-file-line fr-icon--sm text-primary" aria-hidden="true"></span>',
            jpeg: '<span class="fr-icon-file-line fr-icon--sm text-primary" aria-hidden="true"></span>',
            png: '<span class="fr-icon-file-line fr-icon--sm text-primary" aria-hidden="true"></span>',
            default: '<span class="fr-icon-file-line fr-icon--sm" aria-hidden="true"></span>'
        };
        return icons[extension] || icons.default;
    }

    attachEventListeners(element, type) {
        const uploadZone = element.querySelector(`[data-type="${type}"]`);
        const fileInput = element.querySelector(`#file-${type}`);

        if (uploadZone && fileInput) {
            uploadZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.uploadFile(type, e.target.files[0]);
                }
            });

            uploadZone.addEventListener('dragover', e => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });

            uploadZone.addEventListener('dragleave', e => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
            });

            uploadZone.addEventListener('drop', e => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    this.uploadFile(type, e.dataTransfer.files[0]);
                }
            });
        }
    }

    async uploadFile(type, file) {
        if (!this.validateFile(file, type)) return;

        const formData = new FormData();
        formData.append('eleve_id', this.eleveId);
        formData.append('document_type', type);
        formData.append('file', file);

        const progressBar = document.querySelector(`[data-type="${type}"] .fr-progress__bar`);
        const progressContainer = document.querySelector(`[data-type="${type}"] .fr-progress`);
        const messageContainer = document.getElementById(`message-${type}`);

        this.clearMessage(type);
        if (progressContainer) progressContainer.style.display = 'block';

        try {
            const response = await this.uploadWithProgress(formData, type, progressBar);

            if (progressContainer) progressContainer.style.display = 'none';

            if (response.success) {
                this.showMessage(type, response.message, 'success');
                await this.loadDocumentsStatus();
                this.renderDocuments();
                this.updateNextButton();
            } else {
                this.showMessage(type, response.error || 'Erreur lors du téléchargement', 'error');
            }
        } catch (error) {
            if (progressContainer) progressContainer.style.display = 'none';
            console.error('Erreur upload:', error);
            this.showMessage(type, 'Erreur de connexion ou serveur indisponible', 'error');
        }
    }

    uploadWithProgress(formData, type, progressBar) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && progressBar) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBar.style.width = `${percent.toFixed(1)}%`;
                }
            });

            xhr.addEventListener('load', () => {
                try {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                } catch (e) {
                    reject(new Error('Réponse serveur invalide'));
                }
            });

            xhr.addEventListener('error', () => reject(new Error('Erreur de connexion')));
            xhr.addEventListener('timeout', () => reject(new Error('Timeout de connexion')));

            xhr.timeout = 30000;
            xhr.open('POST', '/upload-document');
            xhr.send(formData);
        });
    }

    validateFile(file, type) {
        const allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        const ext = file.name.split('.').pop().toLowerCase();

        if (file.size > 5 * 1024 * 1024) {
            this.showMessage(type, 'Fichier trop volumineux (max 5MB)', 'error');
            return false;
        }

        if (!allowed.includes(ext)) {
            this.showMessage(type, 'Format non autorisé. PDF, JPG ou PNG requis.', 'error');
            return false;
        }

        return true;
    }

    async deleteDocument(type) {
        if (!confirm('Supprimer ce document ?')) return;

        const formData = new FormData();
        formData.append('eleve_id', this.eleveId);
        formData.append('document_type', type);

        try {
            const response = await fetch('/delete-document', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(type, data.message, 'success');
                await this.loadDocumentsStatus();
                this.renderDocuments();
                this.updateNextButton();
            } else {
                this.showMessage(type, data.error || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur suppression:', error);
            this.showMessage(type, 'Erreur de connexion', 'error');
        }
    }

    replaceDocument(type) {
        const fileInput = document.getElementById(`file-${type}`);
        if (fileInput) fileInput.click();
    }

    showMessage(type, message, level) {
        const container = document.getElementById(`message-${type}`);
        if (!container) return;

        const classMap = {
            success: 'fr-alert fr-alert--success',
            error: 'fr-alert fr-alert--error'
        };
        const title = level === 'success' ? 'Succès' : 'Erreur';

        container.innerHTML = `
            <div class="${classMap[level]} fr-alert--sm fr-mt-2w">
                <p class="fr-alert__title">${title}</p>
                <p>${message}</p>
            </div>
        `;

        if (level === 'success') {
            setTimeout(() => this.clearMessage(type), 3000);
        }
    }

    clearMessage(type) {
        const container = document.getElementById(`message-${type}`);
        if (container) container.innerHTML = '';
    }

    showGlobalError(message) {
        const container = document.getElementById('documents-container');
        if (container) {
            container.innerHTML = `<div class="fr-alert fr-alert--error"><p>${message}</p></div>`;
        }
    }

    updateNextButton() {
        const btn = document.getElementById('next-step-btn');
        if (!btn) return;

        if (this.allRequiredUploaded) {
            btn.disabled = false;
            btn.innerHTML = 'Suivant <span class="fr-icon-arrow-right-line" aria-hidden="true"></span>';
        } else {
            const count = this.missingRequired ? this.missingRequired.length : 0;
            btn.disabled = true;
            btn.innerHTML = `Documents manquants (${count})`;
        }
    }
}