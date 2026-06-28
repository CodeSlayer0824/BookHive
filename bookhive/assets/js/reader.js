document.addEventListener('DOMContentLoaded', function() {
    // Initialize PDF.js viewer
    const iframe = document.getElementById('pdf-iframe');
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    const zoomInBtn = document.getElementById('zoom-in');
    const zoomOutBtn = document.getElementById('zoom-out');
    const fullscreenBtn = document.getElementById('fullscreen');
    const addBookmarkBtn = document.getElementById('add-bookmark');
    const closeSidebarBtn = document.getElementById('close-sidebar');
    const sidebar = document.querySelector('.sidebar');
    
    let currentPage = 1;
    let totalPages = 1;
    let zoomLevel = 1;
    let bookId = '';
    
    // Get book ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    bookId = urlParams.get('id');
    
    // Listen for messages from PDF.js viewer
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'pagechange') {
            currentPage = event.data.page;
            updatePageDisplay();
        }
        
        if (event.data && event.data.type === 'documentloaded') {
            totalPages = event.data.pages;
            updatePageDisplay();
            loadTableOfContents();
        }
    });
    
    // Navigation controls
    prevBtn.addEventListener('click', goToPreviousPage);
    nextBtn.addEventListener('click', goToNextPage);
    
    // Zoom controls
    zoomInBtn.addEventListener('click', zoomIn);
    zoomOutBtn.addEventListener('click', zoomOut);
    
    // Fullscreen control
    fullscreenBtn.addEventListener('click', toggleFullscreen);
    
    // Bookmark control
    if (addBookmarkBtn) {
        addBookmarkBtn.addEventListener('click', addBookmark);
    }
    
    // Sidebar control
    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', toggleSidebar);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            goToPreviousPage();
        } else if (e.key === 'ArrowRight') {
            goToNextPage();
        } else if (e.key === '+' || e.key === '=') {
            zoomIn();
        } else if (e.key === '-') {
            zoomOut();
        } else if (e.key === 'f' || e.key === 'F') {
            toggleFullscreen();
        }
    });
    
    function goToPreviousPage() {
        if (currentPage > 1) {
            currentPage--;
            updatePdfViewer();
        }
    }
    
    function goToNextPage() {
        if (currentPage < totalPages) {
            currentPage++;
            updatePdfViewer();
        }
    }
    
    function zoomIn() {
        zoomLevel = Math.min(zoomLevel + 0.1, 2);
        updateZoomDisplay();
        updatePdfViewerZoom();
    }
    
    function zoomOut() {
        zoomLevel = Math.max(zoomLevel - 0.1, 0.5);
        updateZoomDisplay();
        updatePdfViewerZoom();
    }
    
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }
    
    function addBookmark() {
        if (!bookId) return;
        
        fetch('../api/add_bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `book_id=${bookId}&page=${currentPage}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Bookmark added successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
    
    function toggleSidebar() {
        sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
    }
    
    function updatePageDisplay() {
        document.getElementById('page-num').textContent = `Page ${currentPage} of ${totalPages}`;
        
        // Update progress in database if logged in
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn()) {
            const progress = Math.round((currentPage / totalPages) * 100);
            
            fetch('../api/update_page.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_id=${bookId}&page=${currentPage}`
            });
        }
    }
    
    function updateZoomDisplay() {
        document.getElementById('zoom-level').textContent = `${Math.round(zoomLevel * 100)}%`;
    }
    
    function updatePdfViewer() {
        if (iframe.contentWindow && iframe.contentWindow.PDFViewerApplication) {
            iframe.contentWindow.PDFViewerApplication.page = currentPage;
        }
    }
    
    function updatePdfViewerZoom() {
        if (iframe.contentWindow && iframe.contentWindow.PDFViewerApplication) {
            iframe.contentWindow.PDFViewerApplication.pdfViewer.currentScale = zoomLevel;
        }
    }
    
    function loadTableOfContents() {
        const tocList = document.querySelector('.toc-list');
        
        // In a real implementation, you would fetch the TOC from your database
        // For now, we'll create a simple TOC with page numbers
        tocList.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = `Page ${i}`;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = i;
                updatePdfViewer();
                updatePageDisplay();
            });
            
            li.appendChild(a);
            tocList.appendChild(li);
        }
    }
});