/**
 * File Cat - Main Application Logic
 */

const app = {
    currentPath: '/',
    viewMode: localStorage.getItem('fc_viewMode') || 'grid', // grid or list
    selectedItems: new Set(),
    items: [], // Store current loaded items

    init() {
        this.bindEvents();
        this.setViewMode(this.viewMode);
        this.loadPath('/');
    },

    bindEvents() {
        // Drag and Drop
        const dropOverlay = document.getElementById('drop-overlay');
        let dragCounter = 0;

        window.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dragCounter++;
            dropOverlay.style.display = 'flex';
        });

        window.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter === 0) {
                dropOverlay.style.display = 'none';
            }
        });

        window.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        window.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            dropOverlay.style.display = 'none';
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                this.handleFiles(e.dataTransfer.files);
            }
        });

        // Click outside selection
        document.getElementById('files-container').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                this.clearSelection();
            }
        });

        // Checkbox events delegate
        document.getElementById('files-container').addEventListener('change', (e) => {
            if (e.target.classList.contains('item-checkbox')) {
                const itemEl = e.target.closest('.file-item');
                const path = itemEl.dataset.path;
                
                if (e.target.checked) {
                    this.selectedItems.add(path);
                    itemEl.classList.add('item-selected');
                } else {
                    this.selectedItems.delete(path);
                    itemEl.classList.remove('item-selected');
                }
                this.updateSelectionToolbar();
            }
        });
    },

    async loadPath(path) {
        this.currentPath = path;
        this.clearSelection();
        this.showLoading(true);
        
        try {
            const res = await fetch(`api/list.php?path=${encodeURIComponent(path)}`);
            if (!res.ok) throw new Error('Falha ao carregar diretório');
            const data = await res.json();
            
            this.items = data.items;
            this.renderBreadcrumbs(data.current_path);
            this.renderItems();
        } catch (err) {
            alert(err.message);
        } finally {
            this.showLoading(false);
        }
    },

    renderBreadcrumbs(path) {
        const container = document.getElementById('breadcrumbs');
        let html = `<a href="#" onclick="app.loadPath('/'); return false;" class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">Raiz</a>`;
        
        if (path && path !== '/' && path !== '""') {
            const parts = path.replace(/['"]/g, '').split('/').filter(p => p);
            let currentPathBuild = '';
            
            parts.forEach((part, index) => {
                currentPathBuild += (currentPathBuild ? '/' : '') + part;
                html += `
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                `;
                if (index === parts.length - 1) {
                    html += `<span class="text-gray-900 dark:text-white font-medium truncate max-wxs">${part}</span>`;
                } else {
                    html += `<a href="#" onclick="app.loadPath('${currentPathBuild}'); return false;" class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 truncate max-w-xs">${part}</a>`;
                }
            });
        }
        
        container.innerHTML = html;
    },

    renderItems() {
        const container = document.getElementById('files-container');
        const emptyState = document.getElementById('empty-state');
        
        if (this.items.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        }

        emptyState.classList.add('hidden');
        emptyState.classList.remove('flex');

        let html = '';
        if (this.viewMode === 'grid') {
            container.className = 'flex-1 overflow-y-auto p-4 sm:p-6 pb-24 outline-none grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 content-start';
            this.items.forEach(item => html += this.renderGridItem(item));
        } else {
            container.className = 'flex-1 overflow-y-auto p-4 sm:p-6 pb-24 outline-none flex flex-col space-y-2';
            this.items.forEach(item => html += this.renderListItem(item));
        }

        container.innerHTML = html;
    },

    renderGridItem(item) {
        const isFolder = item.type === 'folder';
        let iconHtml = '';
        
        if (isFolder) {
            iconHtml = `<svg class="w-12 h-12 text-blue-400 mb-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>`;
        } else if (item.is_image) {
            iconHtml = `
            <div class="w-20 h-20 mb-2 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600">
                <img src="${item.preview_url}" alt="${item.name}" class="w-full h-full object-cover">
            </div>`;
        } else {
            iconHtml = `<svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>`;
        }
            
        const action = isFolder ? `app.loadPath('${item.path}')` : `app.preview('${item.path}')`;
        const sizeInfo = isFolder ? '' : `<div class="text-xs text-gray-500 mt-1">${item.size_human}</div>`;

        return `
        <div class="file-item relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:shadow-md transition-all select-none"
             data-path="${item.path}" data-type="${item.type}" data-name="${item.name}"
             onclick="if(event.target.tagName !== 'INPUT') { ${action} }">
            
            <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                <input type="checkbox" class="item-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
            </div>
            
            ${iconHtml}
            <div class="w-full truncate text-sm font-medium text-gray-700 dark:text-gray-200" title="${item.name}">${item.name}</div>
            ${sizeInfo}
        </div>`;
    },

    renderListItem(item) {
        const isFolder = item.type === 'folder';
        let iconHtml = '';
        
        if (isFolder) {
            iconHtml = `<svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>`;
        } else if (item.is_image) {
            iconHtml = `
            <div class="w-8 h-8 rounded shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600">
                <img src="${item.preview_url}" alt="${item.name}" class="w-full h-full object-cover">
            </div>`;
        } else {
            iconHtml = `<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>`;
        }
            
        const action = isFolder ? `app.loadPath('${item.path}')` : `app.preview('${item.path}')`;

        return `
        <div class="file-item flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
             data-path="${item.path}" data-type="${item.type}" data-name="${item.name}"
             onclick="if(event.ctrlKey || event.metaKey || event.target.tagName === 'INPUT') { return; } ${action}">
            
            <div class="mr-4 pl-1" onclick="event.stopPropagation()">
                <input type="checkbox" class="item-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
            </div>
            
            <div class="mr-4">${iconHtml}</div>
            <div class="flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-200 block" title="${item.name}">${item.name}</div>
            
            <div class="hidden sm:block w-32 text-xs text-gray-500 dark:text-gray-400">${item.modified_human}</div>
            <div class="w-24 text-right text-xs text-gray-500 dark:text-gray-400 pr-2">${item.size_human || '--'}</div>
        </div>`;
    },

    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('fc_viewMode', mode);
        
        document.getElementById('btn-view-grid').classList.toggle('bg-white', mode === 'grid');
        document.getElementById('btn-view-grid').classList.toggle('dark:bg-gray-600', mode === 'grid');
        document.getElementById('btn-view-grid').classList.toggle('shadow-sm', mode === 'grid');
        
        document.getElementById('btn-view-list').classList.toggle('bg-white', mode === 'list');
        document.getElementById('btn-view-list').classList.toggle('dark:bg-gray-600', mode === 'list');
        document.getElementById('btn-view-list').classList.toggle('shadow-sm', mode === 'list');
        
        if (this.items.length > 0) {
            this.renderItems();
            this.restoreSelection();
        }
    },

    clearSelection() {
        this.selectedItems.clear();
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.file-item').forEach(el => el.classList.remove('item-selected'));
        this.updateSelectionToolbar();
    },

    restoreSelection() {
        document.querySelectorAll('.file-item').forEach(el => {
            if (this.selectedItems.has(el.dataset.path)) {
                el.classList.add('item-selected');
                el.querySelector('.item-checkbox').checked = true;
            }
        });
    },

    updateSelectionToolbar() {
        const toolbar = document.getElementById('selection-toolbar');
        const countSpan = document.getElementById('selection-count');
        const count = this.selectedItems.size;
        
        if (count > 0) {
            countSpan.textContent = count;
            toolbar.classList.remove('hidden');
            
            // Only allow rename if 1 is selected
            const singleBtns = document.querySelectorAll('.action-btn-single');
            singleBtns.forEach(btn => btn.disabled = count !== 1);
            if (count !== 1) {
                singleBtns.forEach(btn => btn.classList.add('opacity-50', 'cursor-not-allowed'));
            } else {
                singleBtns.forEach(btn => btn.classList.remove('opacity-50', 'cursor-not-allowed'));
            }

        } else {
            toolbar.classList.add('hidden');
        }
    },

    showLoading(show) {
        const loader = document.getElementById('loading');
        const container = document.getElementById('files-container');
        const emptyState = document.getElementById('empty-state');
        
        if (show) {
            loader.classList.remove('hidden');
            loader.classList.add('flex');
            container.classList.add('hidden');
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
        } else {
            loader.classList.add('hidden');
            loader.classList.remove('flex');
            container.classList.remove('hidden');
        }
    },

    async handleFiles(files) {
        if (!files || files.length === 0) return;
        
        const modal = document.getElementById('upload-modal');
        const listBody = document.getElementById('upload-list-body');
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth;
        modal.classList.remove('translate-y-full');
        
        listBody.innerHTML = '';
        
        const formData = new FormData();
        formData.append('path', this.currentPath);
        
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
            
            // Add progress UI placeholder (Simplified for MVP without XHR progress event mapping per file)
            listBody.innerHTML += `
            <div class="mb-2" id="up-${i}">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="truncate pr-2">${files[i].name}</span>
                    <span class="shrink-0 text-blue-500 status">Enviando...</span>
                </div>
            </div>`;
        }

        try {
            const res = await fetch('api/upload.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro no upload');
            
            document.getElementById('upload-title').textContent = 'Upload concluído';
            setTimeout(() => {
                modal.classList.add('translate-y-full');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }, 3000);
            
            this.loadPath(this.currentPath);
            
        } catch (err) {
            alert(err.message);
            document.getElementById('upload-title').textContent = 'Erro no upload';
        }
        
        document.getElementById('file-upload').value = ''; // Reset input
    },

    preview(path) {
        const item = this.items.find(i => i.path === path);
        if (item && item.is_image) {
            this.modals.previewImage.open(item);
            return;
        }
        
        window.location.href = `api/download.php?path=${encodeURIComponent(path)}`;
    },

    modals: {
        mkdir: {
            open() {
                document.getElementById('modal-mkdir').classList.remove('hidden');
                setTimeout(() => document.getElementById('input-mkdir').focus(), 100);
            },
            close() {
                document.getElementById('modal-mkdir').classList.add('hidden');
                document.getElementById('input-mkdir').value = '';
            },
            async submit() {
                const name = document.getElementById('input-mkdir').value.trim();
                if (!name) return;
                
                try {
                    const res = await fetch('api/mkdir.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ path: app.currentPath, name })
                    });
                    
                    if (!res.ok) {
                        const data = await res.json();
                        throw new Error(data.error || 'Erro ao criar pasta');
                    }
                    
                    this.close();
                    app.loadPath(app.currentPath);
                } catch (err) {
                    alert(err.message);
                }
            }
        },
        delete: {
            async open() {
                const items = Array.from(app.selectedItems);
                if (items.length === 0) return;
                
                if (!confirm(`Tem certeza que deseja excluir ${items.length} item(ns)?`)) return;
                
                try {
                    // MVP simplification: delete serially
                    for (const path of items) {
                        await fetch('api/delete.php', {
                            method: 'POST', // using POST instead of DELETE since old php/servers sometimes strict
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ path })
                        });
                    }
                    app.loadPath(app.currentPath);
                } catch (err) {
                    alert('Erro ao excluir arquivos');
                }
            }
        },
        rename: {
            async open() {
                const items = Array.from(app.selectedItems);
                if (items.length !== 1) return;
                
                const currentPath = items[0];
                const parts = currentPath.split('/');
                const currentName = parts[parts.length - 1];
                
                const newName = prompt('Novo nome:', currentName);
                if (!newName || newName === currentName) return;
                
                try {
                    const res = await fetch('api/rename.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ path: currentPath, new_name: newName })
                    });
                    
                    if (!res.ok) {
                        const data = await res.json();
                        throw new Error(data.error || 'Erro ao renomear');
                    }
                    
                    app.loadPath(app.currentPath);
                } catch (err) {
                    alert(err.message);
                }
            }
        },
        previewImage: {
            open(item) {
                const modal = document.getElementById('modal-preview-image');
                const img = document.getElementById('preview-image-content');
                const name = document.getElementById('preview-image-name');
                const info = document.getElementById('preview-image-info');
                const downloadBtn = document.getElementById('preview-image-download');
                
                img.src = item.preview_url;
                name.textContent = item.name;
                info.textContent = `${item.size_human} • ${item.modified_human}`;
                downloadBtn.href = `api/download.php?path=${encodeURIComponent(item.path)}`;
                
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            },
            close() {
                const modal = document.getElementById('modal-preview-image');
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }
    }
};

// Start
document.addEventListener('DOMContentLoaded', () => app.init());
