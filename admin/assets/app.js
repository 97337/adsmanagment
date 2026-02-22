/**
 * Ad Management System - Frontend JavaScript
 * Handles: Image upload (click, drag, paste), preview management
 */
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    const removeImage = document.getElementById('removeImage');
    const imageFileInput = document.getElementById('imageFileInput');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');

    if (!uploadZone) return;

    // ===== Click to upload =====
    uploadZone.addEventListener('click', function(e) {
        if (e.target.closest('.upload-remove')) return;
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            uploadFile(this.files[0]);
        }
    });

    // ===== Drag & Drop =====
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files && files[0] && files[0].type.startsWith('image/')) {
            uploadFile(files[0]);
        }
    });

    // ===== Clipboard Paste =====
    document.addEventListener('paste', function(e) {
        const items = e.clipboardData?.items;
        if (!items) return;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.startsWith('image/')) {
                e.preventDefault();
                const blob = items[i].getAsFile();
                if (blob) uploadFile(blob);
                break;
            }
        }
    });

    // ===== Remove Image =====
    if (removeImage) {
        removeImage.addEventListener('click', function(e) {
            e.stopPropagation();
            imageFileInput.value = '';
            showPlaceholder();
        });
    }

    // ===== Upload Function =====
    function uploadFile(file) {
        // Validate
        const maxSize = 2 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!allowedTypes.includes(file.type)) {
            showError('不支持的文件格式，仅支持: JPG, PNG, GIF, WebP');
            return;
        }

        if (file.size > maxSize) {
            showError('文件大小超过限制 (2MB)');
            return;
        }

        // Show progress
        showProgress();

        const formData = new FormData();
        formData.append('image', file);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = '上传中... ' + percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.code === 0 && res.url) {
                    imageFileInput.value = res.url;
                    showPreview(res.url);
                } else {
                    showError(res.msg || '上传失败');
                    showPlaceholder();
                }
            } catch (e) {
                showError('上传响应解析失败');
                showPlaceholder();
            }
        });

        xhr.addEventListener('error', function() {
            showError('上传失败，网络错误');
            showPlaceholder();
        });

        xhr.open('POST', 'upload.php');
        xhr.send(formData);
    }

    // ===== UI Helpers =====
    function showPlaceholder() {
        uploadPlaceholder.style.display = '';
        uploadPreview.style.display = 'none';
        uploadProgress.style.display = 'none';
    }

    function showProgress() {
        uploadPlaceholder.style.display = 'none';
        uploadPreview.style.display = 'none';
        uploadProgress.style.display = '';
        progressFill.style.width = '0%';
        progressText.textContent = '上传中...';
    }

    function showPreview(url) {
        uploadPlaceholder.style.display = 'none';
        uploadProgress.style.display = 'none';
        uploadPreview.style.display = '';
        previewImage.src = url;
    }

    function showError(msg) {
        // Create temporary alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = msg;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.style.animation = 'fadeIn 0.3s';
        document.body.appendChild(alert);

        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }

    // If there's already an uploaded image, show preview
    if (imageFileInput && imageFileInput.value) {
        showPreview(imageFileInput.value);
    }
});
