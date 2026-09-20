/**
 * LibriX File Upload Module
 * Handles profile pictures, organization logos, and book cover uploads
 */

const upload = {
    /**
     * Upload a file to the server
     */
    uploadFile: async function(file, uploadType = 'other') {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('upload_type', uploadType);

        try {
            const response = await fetch(`${api.getBaseUrl()}/upload`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.getToken()}`
                },
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            throw error;
        }
    },

    /**
     * Upload profile picture
     */
    uploadProfilePicture: async function(file) {
        return await this.uploadFile(file, 'profile_picture');
    },

    /**
     * Upload organization logo
     */
    uploadOrgLogo: async function(file) {
        return await this.uploadFile(file, 'org_logo');
    },

    /**
     * Upload book cover
     */
    uploadBookCover: async function(file) {
        return await this.uploadFile(file, 'book_cover');
    },

    /**
     * Validate image file
     */
    validateImage: function(file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!validTypes.includes(file.type)) {
            throw new Error('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        if (file.size > maxSize) {
            throw new Error('File size exceeds 5MB limit.');
        }

        return true;
    },

    /**
     * Preview image before upload
     */
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