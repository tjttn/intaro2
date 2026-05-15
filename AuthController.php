<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController
{
    private UserRepository $userRepository;
    
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }
    
    public function login(Request $request): Response
    {
        if (isset($_SESSION['user_id'])) {
            return new RedirectResponse('/');
        }
        
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        
        $content = $this->render('login.php', ['error' => $error]);
        return new Response($content);
    }
    
    public function doLogin(Request $request): Response
    {
        error_log("=== doLogin POST ===");
        
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        
        error_log("Username: " . $username);
        error_log("Password: " . $password);
        
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            error_log("User NOT found in database");
            $_SESSION['login_error'] = 'Неверный логин или пароль';
            return new RedirectResponse('/login');
        }
        
        error_log("User found! ID: " . $user->getId());
        error_log("Stored hash: " . $user->getPassword());
        
        if (password_verify($password, $user->getPassword())) {
            error_log("PASSWORD VERIFIED! Logging in...");
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            error_log("Session set. Redirecting to /");
            return new RedirectResponse('/');
        }
        
        error_log("Password verification FAILED");
        error_log("Input password: " . $password);
        error_log("Stored hash: " . $user->getPassword());
        
        $_SESSION['login_error'] = 'Неверный логин или пароль';
        return new RedirectResponse('/login');
    }
    
    public function logout(Request $request): Response
    {
        session_destroy();
        return new RedirectResponse('/');
    }
    
    private function render(string $template, array $params = []): string
    {
        extract($params);
        ob_start();
        require __DIR__ . '/../../templates/' . $template;
        return ob_get_clean();
    }
}
