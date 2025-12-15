// Check if user is logged in, redirect to login if not
function requireLogin() {
    fetch('check_session.php')
    .then(response => response.json())
    .then(data => {
        if(!data.logged_in) {
            alert('Please login to continue');
            window.location.href = 'login.html';
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
    });
}

// Update navigation with login status
function updateNavigation() {
    fetch('check_session.php')
    .then(response => response.json())
    .then(data => {
        const authNav = document.getElementById('authNav');
        if(data.logged_in) {
            authNav.innerHTML = '<span style="color: white; margin-right: 10px;">Welcome, ' + data.user.first_name + '!</span> <a href="#" onclick="logout(); return false;" style="color: white;">Logout</a>';
        } else {
            authNav.innerHTML = '<a href="login.html" style="color: white;">Login</a> <a href="register.html" style="color: white; margin-left: 10px;">Register</a>';
        }
    })
    .catch(error => console.error('Error checking session:', error));
}

function logout() {
    fetch('logout.php')
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            localStorage.removeItem('user');
            alert('Logged out successfully!');
            window.location.href = 'login.html';
        }
    })
    .catch(error => console.error('Error:', error));
}
