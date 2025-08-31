    </div> <!-- End of main-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('show');
            
            if (sidebar.classList.contains('show')) {
                mainContent.classList.add('expanded');
            } else {
                mainContent.classList.remove('expanded');
            }
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 1024 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
                document.getElementById('mainContent').classList.remove('expanded');
            }
        });

        // Auto-hide sidebar on mobile when clicking a link
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    document.getElementById('sidebar').classList.remove('show');
                    document.getElementById('mainContent').classList.remove('expanded');
                }
            });
        });

        // Add active class to current page in navigation
        const currentPage = window.location.pathname;
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') && currentPage.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-dismissible')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);

        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });

        // Auto-save form data (for long forms)
        const autoSaveForms = document.querySelectorAll('form[data-autosave]');
        autoSaveForms.forEach(form => {
            const formData = new FormData(form);
            const formKey = form.getAttribute('data-autosave') || 'form_' + Math.random().toString(36).substr(2, 9);
            
            // Save form data every 30 seconds
            setInterval(() => {
                const currentData = new FormData(form);
                const data = {};
                for (let [key, value] of currentData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem(formKey, JSON.stringify(data));
            }, 30000);
            
            // Restore form data on page load
            const savedData = localStorage.getItem(formKey);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(key => {
                        const field = form.querySelector(`[name="${key}"]`);
                        if (field) {
                            field.value = data[key];
                        }
                    });
                } catch (e) {
                    console.error('Error restoring form data:', e);
                }
            }
        });

        // Print functionality
        window.printPage = function() {
            window.print();
        };

        // Export functionality
        window.exportToCSV = function(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename || 'export.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };

        // Search functionality
        window.filterTable = function(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            const filter = input.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        };

        // Sort table functionality
        window.sortTable = function(tableId, columnIndex, type = 'string') {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                if (type === 'number') {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else if (type === 'date') {
                    aVal = new Date(aVal);
                    bVal = new Date(bVal);
                }
                
                if (aVal < bVal) return -1;
                if (aVal > bVal) return 1;
                return 0;
            });
            
            rows.forEach(row => tbody.appendChild(row));
        };

        // Notification system
        window.showNotification = function(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        };

        // Loading state management
        window.showLoading = function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Loading...';
                element.disabled = true;
            }
        };

        window.hideLoading = function(element, originalText) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.innerHTML = originalText;
                element.disabled = false;
            }
        };

        // File upload preview
        window.previewFile = function(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (input.files[0].type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-fluid" alt="Preview">`;
                    } else {
                        preview.innerHTML = `<div class="alert alert-info">File: ${input.files[0].name}</div>`;
                    }
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        };

        // Character counter for text areas
        window.setupCharCounter = function(textareaId, counterId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            
            if (textarea && counter) {
                textarea.addEventListener('input', function() {
                    const remaining = maxLength - this.value.length;
                    counter.textContent = remaining;
                    
                    if (remaining < 0) {
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                    }
                });
            }
        };

        // Auto-save draft functionality
        window.setupDraftSaving = function(formId, draftKey) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            // Restore draft on page load
            const savedDraft = localStorage.getItem(draftKey);
            if (savedDraft) {
                try {
                    const draft = JSON.parse(savedDraft);
                    Object.keys(draft).forEach(key => {
                        const field = form.querySelector(`[name="${key}"]`);
                        if (field) {
                            field.value = draft[key];
                        }
                    });
                } catch (e) {
                    console.error('Error restoring draft:', e);
                }
            }
            
            // Save draft every 10 seconds
            setInterval(() => {
                const formData = new FormData(form);
                const draft = {};
                for (let [key, value] of formData.entries()) {
                    draft[key] = value;
                }
                localStorage.setItem(draftKey, JSON.stringify(draft));
            }, 10000);
            
            // Clear draft on successful submission
            form.addEventListener('submit', function() {
                localStorage.removeItem(draftKey);
            });
        };
    </script>
</body>
</html>
