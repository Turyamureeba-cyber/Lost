<?php
// Client-side validation and AJAX submission
?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Register Form
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(registerForm);
      const password = formData.get('password');
      const confirmPassword = formData.get('confirm_password');
      
      if (password !== confirmPassword) {
        showAlert('Passwords do not match', 'error');
        return;
      }
      
      axios.post('/api/auth/register', {
        username: formData.get('username'),
        email: formData.get('email'),
        password: password
      })
      .then(response => {
        if (response.data.success) {
          window.location.href = '/business/index.php';
        } else {
          showAlert(response.data.message, 'error');
        }
      })
      .catch(error => {
        showAlert(error.response?.data?.message || 'Registration failed', 'error');
      });
    });
  }
  
  // Login Form
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(loginForm);
      
      axios.post('/api/auth/login', {
        email: formData.get('email'),
        password: formData.get('password'),
        remember: formData.get('remember') === 'on'
      })
      .then(response => {
        if (response.data.success) {
          window.location.href = '/business/index.php';
        } else {
          showAlert(response.data.message, 'error');
        }
      })
      .catch(error => {
        showAlert(error.response?.data?.message || 'Login failed', 'error');
      });
    });
  }
  
  // Google One Tap
  window.onload = function() {
    google.accounts.id.initialize({
      client_id: '<?= GOOGLE_CLIENT_ID ?>',
      callback: handleCredentialResponse
    });
    
    google.accounts.id.renderButton(
      document.getElementById('google-signin-button'),
      { theme: 'outline', size: 'large' }
    );
    
    google.accounts.id.prompt();
  };
  
  function handleCredentialResponse(response) {
    axios.post('/auth/google-auth-callback.php', {
      credential: response.credential
    })
    .then(res => {
      window.location.href = '/business/index.php';
    })
    .catch(err => {
      showAlert('Google authentication failed', 'error');
    });
  }
  
  function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `auth-alert ${type}`;
    alert.textContent = message;
    document.querySelector('.auth-card').prepend(alert);
    
    setTimeout(() => alert.remove(), 5000);
  }
});
</script>