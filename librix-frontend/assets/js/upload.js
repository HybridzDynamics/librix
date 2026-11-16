const upload = {

    uploadFile: function(file, uploadType) {
        uploadType = uploadType || 'other';

        const formData = new FormData();
        formData.append('file', file);
        formData.append('upload_type', uploadType);

        return fetch(api.baseUrl + '/upload', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + auth.getToken()
            },
            body: formData
        }).then(function(response) {
            return response.json();
        }).then(function(result) {
            if (result.success) {
                return result.data;
            }
            throw new Error(result.error || 'Upload failed');
        });
    },

    uploadProfilePicture: function(file) {
        return upload.uploadFile(file, 'profile_picture');
    },

    uploadOrgLogo: function(file) {
        return upload.uploadFile(file, 'org_logo');
    },

    uploadBookCover: function(file) {
        return upload.uploadFile(file, 'book_cover');
    },

    validateImage: function(file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;

        if (validTypes.indexOf(file.type) === -1) {
            throw new Error('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        if (file.size > maxSize) {
            throw new Error('File size exceeds 5MB limit.');
        }

        return true;
    },

    previewImage: function(file, previewElement) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewElement.src = e.target.result;
            previewElement.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
};

window.upload = upload;
