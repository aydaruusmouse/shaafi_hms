// public/js/document-viewer.js
function documentViewer() {
    return {
        isLoading: true,
        showViewerSelector: false,
        documentInfo: '',
        fileUrl: '',
        fileName: '',
        fileExtension: '',
        recordId: null,
        pdfJsPath: '',
        
        init() {
            // Get data from the element's dataset
            this.fileUrl = this.$el.dataset.fileUrl;
            this.fileName = this.$el.dataset.fileName;
            this.fileExtension = this.$el.dataset.fileExtension;
            this.recordId = this.$el.dataset.recordId;
            this.pdfJsPath = this.$el.dataset.pdfJsPath || (window.location.origin + '/vendor/pdfjs/web/viewer.html');
            
            this.loadDocument();
        },
        
        loadDocument() {
            const extension = this.fileExtension.toLowerCase();
            
            // Update document info
            this.documentInfo = `${this.fileName} (${extension.toUpperCase()})`;
            
            // Load appropriate viewer based on file type
            if (extension === 'pdf') {
                this.loadPdfViewer();
            } else if (['doc', 'docx'].includes(extension)) {
                this.loadWordViewer();
            } else {
                this.showDownloadOnly();
            }
            
            this.isLoading = false;
        },
        
        loadPdfViewer() {
            const iframe = document.createElement('iframe');
            iframe.src = `${this.pdfJsPath}?file=${encodeURIComponent(this.fileUrl)}`;
            iframe.className = 'w-full h-full border-0';
            iframe.title = 'PDF Viewer';
            iframe.setAttribute('frameborder', '0');
            
            this.$refs.viewerContainer.innerHTML = '';
            this.$refs.viewerContainer.appendChild(iframe);
        },
        
        loadWordViewer() {
            const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(this.fileUrl)}`;
            
            const iframe = document.createElement('iframe');
            iframe.src = officeViewerUrl;
            iframe.className = 'w-full h-full border-0';
            iframe.title = 'Word Document Viewer';
            iframe.setAttribute('frameborder', '0');
            
            this.$refs.viewerContainer.innerHTML = '';
            this.$refs.viewerContainer.appendChild(iframe);
            this.showViewerSelector = true;
        },
        
        showDownloadOnly() {
            this.$refs.viewerContainer.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center p-8 text-center bg-gray-50">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">${this.fileName}</h3>
                    <p class="text-gray-600 mb-4">
                        .${this.fileExtension.toUpperCase()} files cannot be previewed directly.
                        Please download the file to view it.
                    </p>
                    <button onclick="this.downloadFile()" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                        Download Document
                    </button>
                </div>
            `;
            this.showViewerSelector = true;
        },
        
        downloadFile() {
            const link = document.createElement('a');
            link.href = this.fileUrl;
            link.download = this.fileName;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}

// Make it globally available
window.documentViewer = documentViewer;