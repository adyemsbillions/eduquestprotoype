<?php
include('db_connection.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Social Post UI Animated with Improved Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body,
    html {
        height: 100%;
        font-family: 'Helvetica Neue', sans-serif;
        overflow: hidden;
        background: #000;
    }

    .container {
        position: relative;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        height: 100vh;
        background: url('student-reading.jpg') center center / cover no-repeat;
    }

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 110px;
        left: -260px;
        width: 260px;
        height: calc(100% - 110px);
        background: rgba(20, 20, 20, 0.95);
        color: white;
        padding: 20px 15px;
        display: flex;
        flex-direction: column;
        gap: 0px;
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
        transition: all 0.4s ease;
        z-index: 1000;
    }

    .sidebar.show {
        left: 0;
    }

    .sidebar-header {
        display: flex;
        justify-content: flex-end;
    }

    .sidebar-header .close-btn {
        font-size: 22px;
        background: #ff2d55;
        padding: 4px 10px;
        border-radius: 12px;
        cursor: pointer;
    }

    .sidebar .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 500;
        padding: 10px 8px;
        border-radius: 10px;
        transition: background 0.3s ease;
        cursor: pointer;
    }

    .sidebar .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar .menu-item i {
        font-size: 20px;
        min-width: 24px;
        text-align: center;
    }

    /* Top Bar */
    .top-bar {
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .profile-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .profile-info img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    .profile-details {
        display: flex;
        flex-direction: column;
        font-size: 13px;
        color: white;
    }

    .profile-details span:first-child {
        font-weight: bold;
    }

    .thumbs {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .thumbs img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    .close-btn {
        font-size: 24px;
        color: white;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    /* User ID box ("more..") */
    .user-id {
        position: absolute;
        top: 70px;
        left: 20px;
        background: white;
        color: #222;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        z-index: 500;
    }

    .user-id::after {
        content: "›";
        font-weight: bold;
    }

    /* Floating Action Button (FAB) Area */
    .fab-container {
        position: absolute;
        right: 20px;
        bottom: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .fab-buttons {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
        transition: all 0.4s ease;
    }

    .fab-buttons.show {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .fab-button {
        background: rgba(0, 0, 0, 0.7);
        padding: 10px;
        border-radius: 50%;
        color: white;
        font-size: 18px;
        cursor: pointer;
        position: relative;
        transition: background 0.3s ease;
    }

    .notification-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: red;
        color: white;
        font-size: 10px;
        padding: 2px 5px;
        border-radius: 50%;
        font-weight: bold;
    }

    /* Comments */
    .comments {
        position: absolute;
        bottom: 90px;
        left: 20px;
        color: white;
        font-size: 14px;
    }

    .comments .comment {
        margin-bottom: 6px;
    }

    .comments .comment strong {
        font-weight: bold;
    }

    /* Bottom Input Bar */
    .bottom-bar {
        position: absolute;
        bottom: 20px;
        left: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .input-button {
        flex: 1;
        padding: 10px 14px;
        background: #ff2d55;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        color: white;
        cursor: pointer;
    }

    .bottom-icon {
        background: rgba(0, 0, 0, 0.6);
        padding: 8px;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        width: 36px;
        height: 36px;
    }

    /* Scroll Tutorial Animation */
    .scroll-tutorial {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .scroll-tutorial.show {
        opacity: 1;
        pointer-events: auto;
    }

    .scroll-hand {
        font-size: 60px;
        color: white;
        animation: swipeScroll 2s infinite;
    }

    .scroll-text {
        color: white;
        font-size: 18px;
        margin-top: 10px;
        font-weight: bold;
    }

    @keyframes swipeScroll {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }

        50% {
            transform: translateY(100px) rotate(-10deg);
            opacity: 0.8;
        }

        100% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }
    }

    /* Desktop Responsive Styles */
    @media (min-width: 1024px) {
        .container {
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .sidebar {
            width: 300px;
            left: -300px;
            top: 0;
            height: 100%;
            border-radius: 0 20px 20px 0;
        }

        .sidebar.show {
            left: 0;
        }

        .top-bar {
            top: 30px;
            left: 30px;
            right: 30px;
        }

        .profile-info img {
            width: 50px;
            height: 50px;
        }

        .profile-details {
            font-size: 16px;
        }

        .thumbs img {
            width: 40px;
            height: 40px;
        }

        .user-id {
            top: 90px;
            left: 30px;
            font-size: 16px;
            padding: 8px 16px;
        }

        .fab-container {
            right: 30px;
            bottom: 160px;
        }

        .fab-button {
            padding: 12px;
            font-size: 20px;
        }

        .comments {
            bottom: 100px;
            left: 30px;
            font-size: 16px;
        }

        .bottom-bar {
            bottom: 30px;
            left: 30px;
            right: 30px;
            gap: 20px;
        }

        .input-button {
            padding: 12px 20px;
            font-size: 16px;
        }

        .bottom-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }

        .scroll-hand {
            font-size: 80px;
        }

        .scroll-text {
            font-size: 22px;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="close-btn" id="closeSidebar">×</div>
        </div>
        <div class="menu-item"><i class="fa-solid fa-house"></i> Home</div>
        <div class="menu-item"><i class="fa-solid fa-newspaper"></i> News Feed</div>
        <div class="menu-item"><i class="fa-solid fa-user-group"></i> Find Friends</div>
        <div class="menu-item"><i class="fa-solid fa-robot"></i> Chat AI</div>
        <div class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Quiz</div>
        <div class="menu-item"><i class="fa-solid fa-rocket"></i> Join Promoters</div>
        <div class="menu-item"><i class="fa-regular fa-heart"></i> Women Crush Wednesday</div>
        <div class="menu-item"><i class="fa-regular fa-envelope"></i> Messages</div>
        <div class="menu-item"><i class="fa-solid fa-users"></i> Groups</div>
        <div class="menu-item"><i class="fa-solid fa-calendar-days"></i> Events</div>
        <div class="menu-item"><i class="fa-solid fa-badge-check"></i> Verification</div>
    </div>

    <div class="container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="profile-info">
                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="">
                <div class="profile-details">
                    <span>Stella Malone</span>
                    <span>1263</span>
                </div>
            </div>
            <div class="thumbs">
                <img src="https://randomuser.me/api/portraits/men/30.jpg" />
                <img src="https://randomuser.me/api/portraits/women/10.jpg" />
                <img src="https://randomuser.me/api/portraits/men/22.jpg" />
            </div>
            <div class="close-btn">×</div>
        </div>

        <!-- User ID (More) -->
        <div class="user-id" id="moreButton">more..</div>

        <!-- Floating Action Area -->
        <div class="fab-container">
            <div class="fab-buttons" id="fabButtons">
                <div class="fab-button"><i class="fa-solid fa-newspaper"></i></div>
                <div class="fab-button"><i class="fa-regular fa-bell"></i><span class="notification-count">5</span>
                </div>
                <div class="fab-button"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="fab-button"><i class="fa-solid fa-calendar-days"></i></div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments">
            <div class="comment"><strong>Jean Walton:</strong> Awesome. Love it <span>😍</span></div>
            <div class="comment"><strong>Willie Singleton:</strong> Wow... so pretty!</div>
        </div>

        <!-- Bottom Input Row -->
        <div class="bottom-bar">
            <button class="input-button">Academic Materials</button>
            <div class="bottom-icon"><i class="fa-solid fa-arrow-right"></i></div>
            <div class="bottom-icon"><i class="fa-regular fa-envelope"></i></div>
            <div class="bottom-icon" id="fabMainBottom"><i class="fa-solid fa-plus"></i></div>
        </div>

        <!-- Scroll Tutorial -->
        <div class="scroll-tutorial" id="scrollTutorial">
            <i class="fa-solid fa-hand scroll-hand"></i>
            <div class="scroll-text">Swipe down to see more</div>
        </div>
    </div>

    <script>
    const fabMainBottom = document.getElementById('fabMainBottom');
    const fabButtons = document.getElementById('fabButtons');
    const moreButton = document.getElementById('moreButton');
    const sidebar = document.getElementById('sidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const scrollTutorial = document.getElementById('scrollTutorial');

    // Toggle FAB buttons
    function toggleFabButtons() {
        fabButtons.classList.toggle('show');
    }

    fabMainBottom.addEventListener('click', toggleFabButtons);

    // Toggle sidebar
    moreButton.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('More button clicked'); // Debugging
        sidebar.classList.add('show');
    });

    closeSidebar.addEventListener('click', () => {
        sidebar.classList.remove('show');
    });

    // Scroll tutorial for first-time visitors
    if (!localStorage.getItem('hasVisited')) {
        scrollTutorial.classList.add('show');

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            scrollTutorial.classList.remove('show');
        }, 5000);

        // Dismiss on click
        scrollTutorial.addEventListener('click', () => {
            scrollTutorial.classList.remove('show');
        });

        // Mark as visited
        localStorage.setItem('hasVisited', 'true');
    }

    // Adjust layout on window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            sidebar.style.height = '100%';
        } else {
            sidebar.style.height = 'calc(100% - 110px)';
        }
    });
    </script>
</body>

</html>