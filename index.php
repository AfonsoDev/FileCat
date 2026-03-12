<?php
require_once 'auth/auth.php';

// Redireciona para o instalador se não instalado
if (!is_installed()) {
    header('Location: install.php');
    exit;
}

// Força login
require_login();

$user = get_logged_in_username();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Minhas Pastas</title>
    <!-- Config tailwind pra UI Dark -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#f0fdf4',
                100: '#dcfce7',
                500: '#22c55e',
                600: '#16a34a',
                900: '#14532d',
              }
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="assets/css/app.css">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        
        /* Drag n Drop Overlay */
        #drop-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(17, 24, 39, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            border: 4px dashed #3b82f6;
            margin: 10px;
            border-radius: 12px;
        }

        .item-selected {
            background-color: rgba(59, 130, 246, 0.2) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 h-screen flex overflow-hidden">

    <!-- Drag & Drop Global Overlay -->
    <div id="drop-overlay">
        <div class="text-center pointer-events-none">
            <svg class="w-20 h-20 text-blue-500 mx-auto mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            <h2 class="text-3xl font-bold text-white">Solte os Arquivos Aqui</h2>
            <p class="text-blue-200 mt-2">Os arquivos farão upload para a pasta atual aberta</p>
        </div>
    </div>

    <!-- Sidebar Lateral Esquerda -->
    <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col hidden md:flex shrink-0">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700">
            <svg class="w-8 h-8 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
            <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400"><?= htmlspecialchars(APP_NAME) ?></span>
        </div>
        
        <!-- Navegação Primária -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-3">
                <li>
                    <a href="#" onclick="app.loadPath('/'); return false;" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Meu Disco
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Profile Bottom -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                    <?= strtoupper(substr($user, 0, 1)) ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200"><?= htmlspecialchars($user) ?></p>
                    <a href="logout.php" class="text-xs text-red-500 hover:text-red-400">Sair da conta</a>
                </div>
            </div>
            
            <!-- Barra armaz -> Mock (apenas UI por agora) -->
            <div class="mt-4">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>Espaço Usado</span>
                    <span>1.2 GB / 50 GB</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: 45%"></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 bg-gray-50 dark:bg-gray-900">
        
        <!-- Header / Toolbar -->
        <header class="h-16 flex items-center justify-between px-4 sm:px-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 shrink-0">
            
            <!-- Breadcrumbs -->
            <div class="flex items-center flex-1 min-w-0 mr-4 text-sm" id="breadcrumbs">
                <!-- Preenchido via JS -->
            </div>

            <!-- Context Actions (Hidden by default, shows when files are selected) -->
            <div id="selection-toolbar" class="hidden flex items-center bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-4 py-1.5 rounded-lg mr-4 border border-blue-200 dark:border-blue-800">
                <span class="text-sm font-medium mr-4"><span id="selection-count">1</span> selecionado(s)</span>
                
                <button title="Renomear" onclick="app.modals.rename.open()" class="action-btn-single p-1.5 mx-1 hover:bg-white dark:hover:bg-blue-800 rounded-md transition-colors" disabled>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <button title="Excluir" onclick="app.modals.delete.open()" class="p-1.5 mx-1 text-red-600 hover:text-red-700 hover:bg-white dark:hover:bg-red-900/40 rounded-md transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>

            <!-- Global Actions -->
            <div class="flex items-center space-x-3">
                <!-- Toggle View Mode -->
                <div class="bg-gray-100 dark:bg-gray-700 p-1 rounded-lg flex">
                    <button onclick="app.setViewMode('grid')" class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" id="btn-view-grid">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    </button>
                    <button onclick="app.setViewMode('list')" class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" id="btn-view-list">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
                
                <button onclick="app.modals.mkdir.open()" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg flex items-center transition-colors">
                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                    Nova Pasta
                </button>
                
                <button onclick="document.getElementById('file-upload').click()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm flex items-center transition-colors relative overflow-hidden group">
                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload
                    <input type="file" id="file-upload" class="hidden" multiple onchange="app.handleFiles(this.files)">
                </button>
            </div>
        </header>

        <!-- Loading View -->
        <div id="loading" class="hidden flex-1 items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Empty State View -->
        <div id="empty-state" class="hidden flex-1 flex-col items-center justify-center text-center p-8">
            <div class="w-32 h-32 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                <svg class="w-16 h-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Esta pasta está vazia</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-sm mb-6">Arraste e solte arquivos aqui para fazer upload, ou crie uma nova pasta para começar a organizar.</p>
            <button onclick="document.getElementById('file-upload').click()" class="text-blue-600 font-medium hover:underline">Fazer upload de um arquivo</button>
        </div>

        <!-- Files Container (Grid/List content injected here via JS) -->
        <div id="files-container" class="flex-1 overflow-y-auto p-4 sm:p-6 outline-none" tabindex="0">
            <!-- Items injected by JS -->
        </div>
        
    </main>
    
    <!-- Upload Progress Modal -->
    <div id="upload-modal" class="fixed bottom-6 right-6 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 hidden transform transition-all translate-y-full z-50">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50 rounded-t-lg">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white" id="upload-title">Fazendo upload...</h3>
        </div>
        <div class="p-4" id="upload-list-body">
            <!-- Progress bars injected by JS -->
            <div class="mb-2">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="truncate pr-2">imagem.png</span>
                    <span class="shrink-0 text-blue-500">45%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: 45%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Rename, Mkdir, Delete -->
    <!-- Template: Folder Create Modal -->
    <div id="modal-mkdir" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Nova Pasta</h3>
            <input type="text" id="input-mkdir" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none dark:text-white mb-6" placeholder="Nome da pasta">
            <div class="flex justify-end space-x-3">
                <button onclick="app.modals.mkdir.close()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancelar</button>
                <button onclick="app.modals.mkdir.submit()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Criar</button>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="modal-preview-image" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/95 hidden transition-all duration-300 backdrop-blur-sm" onclick="app.modals.previewImage.close()">
        <div class="relative w-full h-full flex items-center justify-center p-4 sm:p-12" onclick="event.stopPropagation()">
            <!-- Close Button -->
            <button onclick="app.modals.previewImage.close()" class="absolute top-6 right-6 p-2 text-white/50 hover:text-white transition-colors z-10 bg-white/10 rounded-full hover:bg-white/20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            
            <!-- Download Button -->
            <a id="preview-image-download" href="#" download class="absolute top-6 right-20 p-2 text-white/50 hover:text-white transition-colors z-10 bg-white/10 rounded-full hover:bg-white/20" title="Download">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0L8 8m4-4v12"></path></svg>
            </a>

            <!-- Image -->
            <div class="max-w-full max-h-full flex flex-col items-center">
                <img id="preview-image-content" src="" alt="Preview" class="max-w-full max-h-[85vh] object-contain rounded shadow-2xl animate-fade-in transition-transform duration-300">
                <div class="mt-6 text-center">
                    <h4 id="preview-image-name" class="text-white text-lg font-medium truncate max-w-lg px-4"></h4>
                    <p id="preview-image-info" class="text-white/60 text-sm mt-1"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Application Logic -->
    <script src="assets/js/app.js"></script>
</body>
</html>
