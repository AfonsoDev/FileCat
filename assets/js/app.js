/**
 * File Cat - Main Application Logic
 */

const app = {
    currentPath: '/',
    viewMode: localStorage.getItem('fc_viewMode') || 'grid', // grid or list
    selectedItems: new Set(),
    items: [], // Store current loaded items
    currentView: 'files', // files or users
    showHidden: localStorage.getItem('fc_showHidden') === 'true',
    theme: localStorage.getItem('fc_theme') || 'dark',
    secretPassword: '', 

    init() {
        this.initTheme();
        this.bindEvents();
        this.setViewMode(this.viewMode);
        this.updateHiddenUI();
        this.loadPath('/');
        this.updates.check(); // Auto check on init
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

    async loadPath(path, password = '') {
        this.currentPath = path;
        this.clearSelection();
        this.showLoading(true);
        
        try {
            const url = `api/list.php?path=${encodeURIComponent(path)}&show_hidden=${this.showHidden}&password=${encodeURIComponent(password || this.secretPassword)}`;
            const res = await fetch(url);
            
            if (res.status === 403) {
                const data = await res.json();
                if (data.needs_password) {
                    this.modals.secretPassword.open('folder', path);
                    return;
                }
            }

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

    toggleHidden() {
        this.showHidden = !this.showHidden;
        localStorage.setItem('fc_showHidden', this.showHidden);
        this.updateHiddenUI();
        this.loadPath(this.currentPath);
    },

    updateHiddenUI() {
        const toggle = document.getElementById('toggle-hidden');
        if (toggle) {
            const dot = toggle.querySelector('.dot');
            const isActive = this.showHidden;
            
            toggle.classList.toggle('bg-blue-600', isActive);
            toggle.classList.toggle('bg-gray-200', !isActive);
            dot.style.transform = isActive ? 'translateX(20px)' : 'translateX(0)';
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

    switchView(view) {
        this.currentView = view;
        
        // Update Sidebar
        const navFiles = document.getElementById('nav-files');
        const navUsers = document.getElementById('nav-users');
        
        const activeClass = ['bg-blue-50', 'text-blue-700', 'dark:bg-blue-900/30', 'dark:text-blue-400'];
        const inactiveClass = ['text-gray-700', 'hover:bg-gray-100', 'dark:text-gray-300', 'dark:hover:bg-gray-700'];

        if (view === 'files') {
            navFiles.classList.add(...activeClass);
            navFiles.classList.remove(...inactiveClass);
            if (navUsers) {
                navUsers.classList.remove(...activeClass);
                navUsers.classList.add(...inactiveClass);
            }
            document.getElementById('view-files').classList.remove('hidden');
            document.getElementById('view-users').classList.add('hidden');
            document.getElementById('breadcrumb').classList.remove('invisible');
            document.querySelectorAll('.btn-global-action').forEach(b => b.classList.remove('hidden'));
            if (document.getElementById('btn-new-user')) document.getElementById('btn-new-user').classList.add('hidden');
            this.loadPath(this.currentPath);
        } else {
            navFiles.classList.remove(...activeClass);
            navFiles.classList.add(...inactiveClass);
            if (navUsers) {
                navUsers.classList.add(...activeClass);
                navUsers.classList.remove(...inactiveClass);
            }
            document.getElementById('view-files').classList.add('hidden');
            document.getElementById('view-users').classList.remove('hidden');
            document.getElementById('breadcrumb').classList.add('invisible');
            document.querySelectorAll('.btn-global-action').forEach(b => b.classList.add('hidden'));
            if (document.getElementById('btn-new-user')) document.getElementById('btn-new-user').classList.remove('hidden');
            this.loadUsers();
        }
    },

    async loadUsers() {
        const listBody = document.getElementById('users-list-body');
        if (!listBody) return;
        listBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">Carregando usuários...</td></tr>';

        try {
            const res = await fetch('api/users.php');
            if (!res.ok) throw new Error('Erro ao carregar usuários');
            const users = await res.json();

            listBody.innerHTML = users.map(user => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold mr-3">
                                ${user.username[0].toUpperCase()}
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">${user.username}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${user.role === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'}">
                            ${user.role === 'admin' ? 'Administrador' : 'Usuário'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        ${user.created_at !== '--' ? new Date(user.created_at).toLocaleDateString() : '--'}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="app.deleteUser('${user.username}')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Excluir Usuário">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            listBody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">${err.message}</td></tr>`;
        }
    },

    async deleteUser(username) {
        if (!confirm(`Tem certeza que deseja excluir o usuário "${username}"?`)) return;

        try {
            const res = await fetch('api/users.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username })
            });

            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.error || 'Erro ao excluir usuário');
            }

            this.loadUsers();
        } catch (err) {
            alert(err.message);
        }
    },

    renderGridItem(item) {
        const isFolder = item.type === 'folder';
        let iconHtml = '';
        
        if (isFolder) {
            iconHtml = `<svg class="w-12 h-12 text-blue-400 mb-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>`;
        } else if (item.is_image) {
            let src = item.preview_url;
            if (item.is_secret) src += `&password=${encodeURIComponent(this.secretPassword)}`;
            iconHtml = `
            <div class="w-20 h-20 mb-2 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600 relative">
                ${item.is_secret ? '<div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10"><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>' : ''}
                <img src="${src}" alt="${item.name}" class="w-full h-full object-cover">
            </div>`;
        } else {
            iconHtml = `
            <div class="relative">
                ${item.is_secret ? '<div class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-1 z-10 scale-75"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>' : ''}
                <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>`;
        }
            
        const sizeInfo = isFolder ? '' : `<div class="text-xs text-gray-500 mt-1">${item.size_human}</div>`;

        return `
        <div class="file-item relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:shadow-md transition-all select-none"
             data-path="${item.path}" data-type="${item.type}" data-name="${item.name}"
             onclick="if(event.target.tagName !== 'INPUT') { app.handleItemClick(this.dataset.path, this.dataset.type) }">
            
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
            let src = item.preview_url;
            if (item.is_secret) src += `&password=${encodeURIComponent(this.secretPassword)}`;
            iconHtml = `
            <div class="w-8 h-8 rounded shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600 relative">
                ${item.is_secret ? '<div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10"><svg class="w-4 h-4 text-white scale-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>' : ''}
                <img src="${src}" alt="${item.name}" class="w-full h-full object-cover">
            </div>`;
        } else {
            iconHtml = `
            <div class="relative">
                ${item.is_secret ? '<div class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 z-10 scale-[0.6]"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>' : ''}
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>`;
        }
            
        return `
        <div class="file-item flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
             data-path="${item.path}" data-type="${item.type}" data-name="${item.name}"
             onclick="if(event.ctrlKey || event.metaKey || event.target.tagName === 'INPUT') { return; } app.handleItemClick(this.dataset.path, this.dataset.type)">
            
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

    initTheme() {
        document.documentElement.classList.toggle('dark', this.theme === 'dark');
        this.updateThemeUI();
    },

    toggleTheme() {
        this.theme = (this.theme === 'dark') ? 'light' : 'dark';
        localStorage.setItem('fc_theme', this.theme);
        document.documentElement.classList.toggle('dark', this.theme === 'dark');
        this.updateThemeUI();
    },

    updateThemeUI() {
        const toggle = document.getElementById('toggle-theme');
        if (toggle) {
            const dot = toggle.querySelector('.dot');
            const isDark = this.theme === 'dark';
            
            toggle.classList.toggle('bg-blue-600', isDark);
            toggle.classList.toggle('bg-gray-200', !isDark);
            dot.style.transform = isDark ? 'translateX(20px)' : 'translateX(0)';
        }
    },

    switchView(view) {
        this.currentView = view;
        const navFiles = document.getElementById('nav-files');
        const navUsers = document.getElementById('nav-users');
        const navSettings = document.getElementById('nav-settings');
        
        const viewFiles = document.getElementById('view-files');
        const viewUsers = document.getElementById('view-users');
        const viewSettings = document.getElementById('view-settings');
        
        const btnNewUser = document.getElementById('btn-new-user');
        const globalActions = document.querySelectorAll('.btn-global-action');

        // Reset all views
        [viewFiles, viewUsers, viewSettings].forEach(v => v ? v.classList.add('hidden') : null);
        
        // Reset all nav styles
        const activeClasses = ['bg-blue-50', 'text-blue-700', 'dark:bg-blue-900/30', 'dark:text-blue-400'];
        const inactiveClasses = ['text-gray-700', 'dark:text-gray-300'];
        
        [navFiles, navUsers, navSettings].forEach(nav => {
            if (!nav) return;
            nav.classList.remove(...activeClasses);
            nav.classList.add(...inactiveClasses);
        });

        if (view === 'files') {
            viewFiles.classList.remove('hidden');
            navFiles.classList.add(...activeClasses);
            navFiles.classList.remove(...inactiveClasses);
            if (btnNewUser) btnNewUser.classList.add('hidden');
            globalActions.forEach(el => el.classList.remove('hidden'));
        } else if (view === 'users') {
            viewUsers.classList.remove('hidden');
            if (navUsers) {
                navUsers.classList.add(...activeClasses);
                navUsers.classList.remove(...inactiveClasses);
            }
            if (btnNewUser) btnNewUser.classList.remove('hidden');
            globalActions.forEach(el => el.classList.add('hidden'));
            this.loadUsers();
        } else if (view === 'settings') {
            viewSettings.classList.remove('hidden');
            navSettings.classList.add(...activeClasses);
            navSettings.classList.remove(...inactiveClasses);
            if (btnNewUser) btnNewUser.classList.add('hidden');
            globalActions.forEach(el => el.classList.add('hidden'));
            this.updates.check();
        }
    },

    toggleSelectAll() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            const itemEl = cb.closest('.file-item');
            const path = itemEl.dataset.path;
            
            if (cb.checked) {
                this.selectedItems.add(path);
                itemEl.classList.add('item-selected');
            } else {
                this.selectedItems.delete(path);
                itemEl.classList.remove('item-selected');
            }
        });
        
        this.updateSelectionToolbar();
    },

    downloadSelected() {
        const paths = Array.from(this.selectedItems).join(',');
        if (!paths) return;
        
        let url = `api/zip.php?paths=${encodeURIComponent(paths)}`;
        if (this.secretPassword) url += `&password=${encodeURIComponent(this.secretPassword)}`;
        
        window.location.href = url;
    },

    updates: {
        async check() {
            try {
                const res = await fetch('api/update.php?action=check');
                if (!res.ok) return;
                const data = await res.json();
                
                // Debug log for troubleshooting issues on the server
                if (data.debug) {
                    console.log('Update Check Debug:', data.debug);
                }
                
                document.getElementById('local-commit').textContent = data.local_version || '--';
                document.getElementById('remote-commit').textContent = data.remote_version || '--';
                document.getElementById('last-update-check').textContent = data.last_check;
                
                const badge = document.getElementById('update-status-badge');
                const btnUpdate = document.getElementById('btn-apply-update');
                
                if (data.update_available) {
                    badge.textContent = 'Atualização Disponível';
                    badge.className = 'px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/10 dark:text-orange-400';
                    btnUpdate.disabled = false;
                } else {
                    badge.textContent = 'Atualizado';
                    badge.className = 'px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/10 dark:text-green-400';
                    btnUpdate.disabled = true;
                }
            } catch (err) {
                console.error('Erro ao verificar atualizações:', err);
            }
        },
        async apply() {
            if (!confirm('Tem certeza que deseja atualizar o sistema? Alterações locais não commitadas podem ser perdidas.')) return;
            
            const btn = document.getElementById('btn-apply-update');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Atualizando...';
            
            try {
                const res = await fetch('api/update.php?action=apply');
                const data = await res.json();
                
                if (data.success) {
                    alert('Sistema atualizado com sucesso! Recarregando...');
                    window.location.reload();
                } else {
                    alert('Erro na atualização: ' + (data.error || 'Erro desconhecido'));
                    console.error(data.output);
                }
            } catch (err) {
                alert('Erro de conexão ao atualizar');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
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

    handleItemClick(path, type) {
        if (type === 'folder') {
            this.loadPath(path);
        } else {
            this.preview(path);
        }
    },

    preview(path) {
        // Find item in the current collection
        const item = this.items.find(i => i.path === path);
        if (item && item.is_secret && !this.secretPassword) {
            this.modals.secretPassword.open('file', path);
            return;
        }

        if (item && item.is_image) {
            this.modals.previewImage.open(item);
            return;
        }
        
        // Fallback or non-image: download
        let url = `api/download.php?path=${encodeURIComponent(path)}`;
        if (this.secretPassword) url += `&password=${encodeURIComponent(this.secretPassword)}`;
        window.location.href = url;
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
            open() {
                const items = Array.from(app.selectedItems);
                if (items.length !== 1) return;
                
                const currentPath = items[0];
                const parts = currentPath.split('/');
                const currentName = parts[parts.length - 1];
                
                document.getElementById('input-rename').value = currentName;
                document.getElementById('modal-rename').classList.remove('hidden');
                setTimeout(() => document.getElementById('input-rename').focus(), 100);
            },
            close() {
                document.getElementById('modal-rename').classList.add('hidden');
                document.getElementById('input-rename').value = '';
            },
            async submit() {
                const items = Array.from(app.selectedItems);
                const currentPath = items[0];
                const newName = document.getElementById('input-rename').value.trim();
                
                const parts = currentPath.split('/');
                const currentName = parts[parts.length - 1];

                if (!newName || newName === currentName) {
                    this.close();
                    return;
                }
                
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
                    
                    this.close();
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
                
                if (!item) return;

                let src = item.preview_url;
                if (item.is_secret) src += `&password=${encodeURIComponent(app.secretPassword)}`;

                img.src = src;
                name.textContent = item.name;
                info.textContent = `${item.size_human || ''} • ${item.modified_human || ''}`;
                
                let downloadUrl = `api/download.php?path=${encodeURIComponent(item.path)}`;
                if (item.is_secret) downloadUrl += `&password=${encodeURIComponent(app.secretPassword)}`;
                downloadBtn.href = downloadUrl;
                
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            },
            close() {
                const modal = document.getElementById('modal-preview-image');
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        },
        newUser: {
            open() {
                document.getElementById('modal-new-user').classList.remove('hidden');
                setTimeout(() => document.getElementById('input-new-user-name').focus(), 100);
            },
            close() {
                document.getElementById('modal-new-user').classList.add('hidden');
                document.getElementById('input-new-user-name').value = '';
                document.getElementById('input-new-user-pass').value = '';
                document.getElementById('input-new-user-role').value = 'user';
            },
            async submit() {
                const username = document.getElementById('input-new-user-name').value.trim();
                const password = document.getElementById('input-new-user-pass').value;
                const role = document.getElementById('input-new-user-role').value;

                if (!username || !password) return alert('Preencha todos os campos');

                try {
                    const res = await fetch('api/users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, password, role })
                    });

                    if (!res.ok) {
                        const data = await res.json();
                        throw new Error(data.error || 'Erro ao criar usuário');
                    }

                    this.close();
                    app.loadUsers();
                } catch (err) {
                    alert(err.message);
                }
            }
        },
        secretPassword: {
            targetType: '',
            targetPath: '',
            open(type, path) {
                this.targetType = type;
                this.targetPath = path;
                document.getElementById('modal-secret-password').classList.remove('hidden');
                setTimeout(() => document.getElementById('input-secret-password').focus(), 100);
            },
            close() {
                document.getElementById('modal-secret-password').classList.add('hidden');
                document.getElementById('input-secret-password').value = '';
            },
            async submit() {
                const pass = document.getElementById('input-secret-password').value;
                if (!pass) return;

                app.secretPassword = pass;
                this.close();

                if (this.targetType === 'folder') {
                    app.loadPath(this.targetPath);
                } else {
                    app.preview(this.targetPath);
                }
            }
        }
    }
};

// Start
document.addEventListener('DOMContentLoaded', () => app.init());
