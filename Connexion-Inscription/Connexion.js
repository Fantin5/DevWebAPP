document.getElementById('uploadIcon').addEventListener('click', function() {
    // Trigger the file input click when the icon is clicked
    document.getElementById('imageInput').click(uploadIcon.style.opacity = 0);
});

document.getElementById('imageInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('selectedImage');
            img.src = e.target.result;
            img.style.display = 'block'; // Make the image visible
        };
        reader.readAsDataURL(file);
    } else {
        alert('Please select a valid image.');
    }
});