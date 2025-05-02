document.getElementById('shareButton1').addEventListener('click', function() {
    const postId = '12345'; // Replace with actual post ID
    const baseUrl = 'https://example.com/post'; // Replace with your base URL
    const shareLink = `${baseUrl}/${postId}`;

    // Set the share link in the input field
    document.getElementById('shareLink').value = shareLink;

    // Show the modal
    document.getElementById('shareModal').style.display = 'block';
});

document.querySelector('.close').addEventListener('click', function() {
    // Hide the modal
    document.getElementById('shareModal').style.display = 'none';
});

document.getElementById('copyIcon').addEventListener('click', function() {
    // Select the link text
    const copyText = document.getElementById('shareLink');
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text
    document.execCommand('copy');

    // Provide feedback to the user
    const feedback = document.createElement('div');
    feedback.textContent = 'Link copied to clipboard!';
    feedback.style.position = 'fixed';
    feedback.style.bottom = '20px';
    feedback.style.right = '20px';
    feedback.style.backgroundColor = '#333';
    feedback.style.color = '#fff';
    feedback.style.padding = '10px';
    feedback.style.borderRadius = '5px';
    document.body.appendChild(feedback);

    // Remove feedback after 2 seconds
    setTimeout(() => {
        document.body.removeChild(feedback);
    }, 2000);
});

// Close the modal if the user clicks outside of it
window.onclick = function(event) {
    if (event.target === document.getElementById('shareModal')) {
        document.getElementById('shareModal').style.display = 'none';
    }
}
