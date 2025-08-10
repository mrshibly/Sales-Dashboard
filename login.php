<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Database.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->email = $_POST['email'];
    $email_exists = $user->emailExists();

    if ($email_exists && password_verify($_POST['password'], $user->password)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user->id;
        $_SESSION['name'] = $user->name;
        $_SESSION['role'] = $user->role;
        
        // Fetch user details including geographic IDs
        $user->id = $_SESSION['id'];
        $user->readOne();

        $_SESSION['division_id'] = $user->division_id;
        $_SESSION['district_id'] = $user->district_id;
        $_SESSION['upazila_id'] = $user->upazila_id;
        $_SESSION['territory_id'] = $user->territory_id;

        header('location: index.php');
    } else {
        $login_err = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
    </head>
<body>
    <img src="Login.gif" alt="Login Animation" style="width: 300px;height: 300px;">
    <div class="login-container">
        <h2>Login</h2>
        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
        <div class="social-login">
            <button id="google-signin" class="btn btn-google"><i class="fab fa-google"></i> Sign in with Google</button>
            <button id="facebook-signin" class="btn btn-facebook"><i class="fab fa-facebook-f"></i> Sign in with Facebook</button>
        </div>
    </div>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";

        // Your web app's Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyCR2VH7JOGI-_zr14SqgJ-k-9Vq30isfbU",
            authDomain: "srms-87697.firebaseapp.com",
            projectId: "srms-87697",
            storageBucket: "srms-87697.appspot.com",
            messagingSenderId: "821176450677",
            appId: "1:821176450677:web:8975c5cd6db1c08f0e423c"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        document.getElementById('google-signin').addEventListener('click', () => {
            const provider = new GoogleAuthProvider();
            signInWithPopup(auth, provider)
                .then((result) => {
                    result.user.getIdToken().then((idToken) => {
                        fetch('api/firebase-auth.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ idToken: idToken })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.message.includes('Successful')) {
                                window.location.href = 'index.php';
                            } else {
                                alert('Login failed: ' + data.message);
                            }
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                            alert('An error occurred during sign-in.');
                        });
                    });
                });
        });
    </script>
</body>
</html>