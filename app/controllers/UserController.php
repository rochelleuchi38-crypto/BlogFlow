<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: UserController
 * 
 * Automatically generated via CLI.
 */


class UserController extends Controller {
public function __construct()
{
    parent::__construct();
    // Load the datetime helper for Manila timezone formatting
    require_once __DIR__ . '/../helpers/datetime_helper.php';
    // Datetime helper is loaded via autoload.php
    require_once __DIR__ . '/../helpers/location.php';

    // Load user data and notifications if user is logged in
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user'])) {
        $this->call->model('UsersModel');
        $user_id = $_SESSION['user']['id'];
        
        // Get unread notifications count
        $unread_count = $this->UsersModel->get_unread_notifications_count($user_id);

        // Get total notifications count
        $total_notifications_count = $this->UsersModel->get_total_notifications_count($user_id);

        // Get recent unread notifications (e.g., last 5)
        $notifications = $this->UsersModel->get_unread_notifications($user_id, 5);

        // Make these available to all views using the session
        $_SESSION['unread_count'] = $unread_count;
        $_SESSION['total_notifications_count'] = $total_notifications_count;
        $_SESSION['notifications'] = $notifications;
        $_SESSION['logged_in_user'] = $_SESSION['user'];
    }
}
    // inside UserController class
protected $allowedFonts = [
    'Roboto', 
    'Poppins', 
    'Lora', 
    'Montserrat', 
    'Playfair Display', 
    'Open Sans'
];


    /**
     * Ensure PHP session is available before accessing $_SESSION.
     */
    private function ensureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Helper for standard JSON responses.
     */
    private function jsonResponse($success, $message = '', $extra = [], $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extra));
    }


    /**
     * Ensure that the current session belongs to an authenticated admin.
     */
    private function requireAdmin()
    {
        $this->ensureSession();

        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(false, 'Not logged in', [], 401);
            exit;
        }

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->jsonResponse(false, 'Forbidden', [], 403);
            exit;
        }

        return $_SESSION['user'];
    }

    /**
     * Attempt to verify a code for the pending email address.
     */
    private function attemptVerification($code, $email)
    {
        $this->call->model('UsersModel');
        $this->ensureSession();

        if (!$email) {
            return [
                'success' => false,
                'message' => 'Your verification session expired. Please register again.'
            ];
        }

        $user = $this->UsersModel->get_user_by_email($email);

        if (!$user) {
            unset($_SESSION['pending_email']);
            return [
                'success' => false,
                'message' => 'Account not found. Please register again.'
            ];
        }

        if ($user['verification_code'] == $code) {
            $this->UsersModel->verify_email($email);
            unset($_SESSION['pending_email']);
            $_SESSION['success_message'] = '✅ Email verified successfully! You can now log in.';

            return [
                'success' => true,
                'message' => 'Email verified successfully!'
            ];
        }

        return [
            'success' => false,
            'message' => '❌ Invalid verification code. Please try again.'
        ];
    }
    
    public function index()
    {
         $this->call->model('UsersModel');

         // Check if user is logged in
         if (!isset($_SESSION['user'])) {
             redirect('/');
             exit;
         }

         // Get logged-in user info
         $logged_in_user = $_SESSION['user']; 
         $data['logged_in_user'] = $logged_in_user;

         // Redirect regular users to user page
         if ($logged_in_user['role'] !== 'admin') {
             redirect('/users/user-page');
             exit;
         }


        $page = 1;
        if(isset($_GET['page']) && ! empty($_GET['page'])) {
            $page = $this->io->get('page');
        }

        $q = '';
        if(isset($_GET['q']) && ! empty($_GET['q'])) {
            $q = trim($this->io->get('q'));
        }

        $records_per_page = 5;

        $user = $this->UsersModel->page($q, $records_per_page, $page);
        $data['users'] = $user['records'];
        $total_rows = $user['total_rows'];

        $this->pagination->set_options([
            'first_link'     => '⏮ First',
            'last_link'      => 'Last ⏭',
            'next_link'      => 'Next →',
            'prev_link'      => '← Prev',
            'page_delimiter' => '&page='
        ]);
        $this->pagination->set_theme('custom');
        $this->pagination->set_custom_classes([

        'nav'    => 'flex justify-center mt-6',
         'ul'     => 'flex space-x-2',
         'li'     => 'list-none',
         'a'      => 'px-3 py-1 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-blue-500 hover:text-white transition',
        'active' => 'bg-blue-600 text-white font-bold border-blue-600'

        ] );


        $this->pagination->initialize($total_rows, $records_per_page, $page, 'users?q='.$q);
        $data['page'] = $this->pagination->paginate();
        $this->call->view('users/index', $data);
    }
   
   public function register()
{
    $this->call->model('UsersModel'); 
    $this->call->library('session');

    if ($this->io->method() === 'post') {
        // --- POST: handle registration ---
        $username = trim($this->io->post('username'));
        $email = trim($this->io->post('email'));
        $password = password_hash($this->io->post('password'), PASSWORD_BCRYPT);
        $role = trim($_POST['role'] ?? 'user');

        // Check username/email uniqueness
        if (!$this->UsersModel->is_username_unique($username)) {
            $_SESSION['register_error'] = 'Username already exists.';
            header('Location: /auth/register');
            exit;
        }

        if ($this->UsersModel->is_email_exists($email)) {
            $_SESSION['register_error'] = 'Email already registered.';
            header('Location: /auth/register');
            exit;
        }

        // Insert user
        $verification_code = rand(100000, 999999);
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'verification_code' => $verification_code,
            'is_verified' => 0,
            'created_at' => current_manila_datetime()
        ];

        if ($this->UsersModel->insert($data)) {
            // Send verification email
            $this->call->library('email');

            $email_instance = null;
            if (isset($this->email) && is_object($this->email)) {
                $email_instance = $this->email;
            } else {
                $LAVA =& lava_instance();
                if (isset($LAVA->properties['email']) && is_object($LAVA->properties['email'])) {
                    $email_instance = $LAVA->properties['email'];
                }
            }

             if ($email_instance) {
                $email_instance->sender('rochelleuchi38@gmail.com', 'Blogflow');
                if ($email_instance->recipient($email)) {
                    $email_instance->subject('Verify your Blogflow account');
                    $htmlContent = "<h2>Hello " . htmlspecialchars($username) . ",</h2>";
                    $htmlContent .= "<p>Your verification code is:</p>";
                    $htmlContent .= "<h3 style='color:#014421;'>{$verification_code}</h3>";
                    $htmlContent .= "<p>Please enter this code to verify your account.</p>";
                    $email_instance->email_content($htmlContent, 'html');
                    $email_instance->send();
                }
            }

            // --- Set pending email for verification ---
            $_SESSION['pending_email'] = $email;
            $_SESSION['verify_notice'] = 'Registration successful! Please check your email.';

            header('Location: /auth/verify');
            exit;
        } else {
            $_SESSION['register_error'] = 'Registration failed. Try again.';
            header('Location: /auth/register');
            exit;
        }
    }

    // --- GET: show registration page ---
    $error = $_SESSION['register_error'] ?? '';
    
    // --- INCLUDE view first so $error can be displayed ---
    require_once __DIR__ . '/../views/pages/Register.php';

    // --- UNSET session after view is included ---
    unset($_SESSION['register_error']);
}


public function verify()
{
    $this->ensureSession();

    // Prevent direct access without pending email
    if (!isset($_SESSION['pending_email'])) {
        redirect('/auth/register');
        exit;
    }

    // Display verification form
    $this->call->view('/pages/Verify');
}

public function verify_code()
{
    $this->ensureSession();

    if ($this->io->method() == 'post') {
        $code = trim($this->io->post('code'));
        $email = $_SESSION['pending_email'] ?? null;

        $result = $this->attemptVerification($code, $email);

        if ($result['success']) {
            $_SESSION['verify_success'] = 'Email verified successfully!';
            unset($_SESSION['pending_email']);
            redirect('/');
        } else {
            $_SESSION['verify_error'] = $result['message'] ?? 'Invalid verification code.';
            $this->call->view('/pages/Verify');
        }
    } else {
        redirect('/auth/verify');
    }
}


public function login()
{
    $this->call->model('UsersModel');
    $this->call->library('auth');

    // If already logged in → redirect to user dashboard
    if (!empty($_SESSION['user'])) {
        redirect('/users/user-page');  // ← GO TO DASHBOARD
        return;
    }

    // POST: process login
    if ($this->io->method() == 'post') {
        $username = $this->io->post('username');
        $password = $this->io->post('password');

        if (empty($username) || empty($password)) {
            $data['error'] = 'Username and password are required';
            $this->call->view('pages/Login', $data);
            return;
        }

        $user = $this->UsersModel->get_user_by_username($username)
             ?: $this->UsersModel->get_user_by_email($username);

        if (!$user) {
            $data['error'] = 'Account not found!';
            $this->call->view('pages/Login', $data);
            return;
        }

        if ($user['is_verified'] == 0) {
            $data['error'] = 'Your account is not verified yet. Please check your email.';
            $this->call->view('pages/Login', $data);
            return;
        }

        if (!password_verify($password, $user['password'])) {
            $data['error'] = 'Incorrect username or password!';
            $this->call->view('pages/Login', $data);
            return;
        }

        // Login successful
        $user_data = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
            'logged_in'=> true
        ];

        $this->auth->login($user['username'], $password);
        $_SESSION['user'] = $user_data;

        redirect('/users/user-page');  // ← SUCCESS → DASHBOARD
        return;
    }

    // GET request → show login page
    $this->call->view('pages/Login');
}

public function logout()
{
    $this->call->library('auth');

    // Destroy LavaLust session
    $this->auth->logout();

    // Destroy native PHP session
    if (isset($_SESSION['user'])) {
        unset($_SESSION['user']);
    }

    session_destroy();  // VERY IMPORTANT

    redirect('/');    // ← show login page
}



public function user_page()
{
    // Redirect to login if not logged in
    if (!isset($_SESSION['user'])) {
        redirect('/'); // or '/login'
        return;
    }

    $this->call->model('UsersModel');

    $logged_in_user = $_SESSION['user'];
    $user_id = $logged_in_user['id'];

    // Get search query from GET parameter
    $searchQuery = trim($this->io->get('search') ?? '');

    // If post_id is specified (e.g., from notification), show all posts but highlight the specific post
    if (isset($_GET['post_id'])) {
        $target_post_id = $_GET['post_id'];
        $posts = $this->UsersModel->get_all_posts();
        $user_results = [];
    } elseif ($searchQuery !== '') {
        // Filter users by username (optional)
        $user_results = $this->UsersModel->db->table('users')
            ->like('username', '%'.$searchQuery.'%')
            ->get_all();

        // Filter posts by category OR author username
        $postsQuery = $this->UsersModel->db->table('posts')
            ->join('users', 'users.id = posts.user_id')
            ->like('posts.category', '%'.$searchQuery.'%')
            ->or_like('users.username', '%'.$searchQuery.'%')
            ->order_by('posts.created_at', 'DESC');

        $posts = $postsQuery->get_all();
    } else {
        // No search, get all posts for display
        $posts = $this->UsersModel->get_all_posts();
        $user_results = [];
    }

    // Get user's own posts for analytics
    $user_posts = $this->UsersModel->get_posts_by_user($user_id);

    // Add likes, comments, and replies info to global posts for display
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $post['is_liked'] = $this->UsersModel->is_post_liked($post['post_id'], $user_id);
            $post['like_count'] = $this->UsersModel->get_like_count($post['post_id']);
            $post['comments'] = $this->UsersModel->get_comments_by_post($post['post_id']);

            if (!empty($post['comments'])) {
                foreach ($post['comments'] as &$comment) {
                    $comment['replies'] = $this->UsersModel->get_replies_by_comment($comment['comment_id']);
                }
                unset($comment);
            }
        }
        unset($post);
    }

    // Add likes, comments, and replies info to user's own posts for analytics
    if (!empty($user_posts)) {
        foreach ($user_posts as &$post) {
            $post['like_count'] = $this->UsersModel->get_like_count($post['post_id']);
            $post['comments'] = $this->UsersModel->get_comments_by_post($post['post_id']);

            if (!empty($post['comments'])) {
                foreach ($post['comments'] as &$comment) {
                    $comment['replies'] = $this->UsersModel->get_replies_by_comment($comment['comment_id']);
                }
                unset($comment);
            }
        }
        unset($post);
    }

    // Unread notifications count
    $unreadCount = $this->UsersModel->get_unread_notifications_count($user_id);

    // Determine analytics data source based on user role
    $analytics_data = ($logged_in_user['role'] === 'admin') ? $posts : $user_posts;

    // Analytics
    $totalPosts = count($analytics_data);
    $totalLikes = array_sum(array_column($analytics_data, 'like_count'));
    $totalComments = array_sum(array_map(fn($p) => count($p['comments'] ?? []), $analytics_data));
    $totalEngagements = $totalLikes + $totalComments;

    // Calculate media counts
    $totalMedia = 0;
    $images = 0;
    $videos = 0;
    foreach ($analytics_data as $post) {
        if (!empty($post['media_path'])) {
            $media = json_decode($post['media_path'], true);
            if (is_array($media)) {
                foreach ($media as $file) {
                    $totalMedia++;
                    if (strpos($file, 'images/') !== false) {
                        $images++;
                    } elseif (strpos($file, 'videos/') !== false) {
                        $videos++;
                    }
                }
            }
        }
    }

    $analytics = [
        'totalPosts' => $totalPosts,
        'totalLikes' => $totalLikes,
        'totalComments' => $totalComments,
        'totalEngagements' => $totalEngagements,
        'totalMedia' => $totalMedia,
        'images' => $images,
        'videos' => $videos,
        'lastPostDate' => $analytics_data[0]['created_at'] ?? 'No posts yet'
    ];

    // Top categories based on analytics data
    $categoryCounts = [];
    foreach ($analytics_data as $post) {
        if (!empty($post['category'])) {
            $categoryCounts[$post['category']] = ($categoryCounts[$post['category']] ?? 0) + 1;
        }
    }
    arsort($categoryCounts);
    $topCategories = array_slice($categoryCounts, 0, 4, true);
    $topCategories = array_map(fn($name, $count) => ['name' => $name, 'count' => $count], array_keys($topCategories), $topCategories);

    // Render view
    $this->call->view('pages/UserPage', [
        'user' => $logged_in_user,
        'posts' => $posts,
        'analytics' => $analytics,
        'topCategories' => $topCategories,
        'unreadCount' => $unreadCount,
        'searchQuery' => $searchQuery,
        'user_results' => $user_results
    ]);
}



public function post_page()
    {
        // This endpoint is no longer needed - Vue handles post creation
        // Return JSON for backward compatibility
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Use /post/create route in Vue frontend'
        ]);
}

    
public function create_post() 
{
    // Load model
    $this->call->model('UsersModel');

    // Require login
    if (!isset($_SESSION['user'])) {
        $_SESSION['error_message'] = 'You must be logged in.';
        redirect('/');
        return;
    }

    // --- SHOW VIEW FOR GET REQUEST ---
    if ($this->io->method() === 'get') {
        $error = $_SESSION['error_message'] ?? null;
        $success = $_SESSION['success_message'] ?? null;

        unset($_SESSION['error_message'], $_SESSION['success_message']);

        return $this->call->view('pages/CreatePost', [
            'error' => $error,
            'success' => $success,
            'user' => $_SESSION['user'],
            'unreadCount' => $this->UsersModel->get_unread_notifications_count($_SESSION['user']['id']),
            'notifications' => $this->UsersModel->get_notifications($_SESSION['user']['id'], 5) // Get last 5 notifications
        ]);
    }

    // --- HANDLE POST REQUEST ---
    if ($this->io->method() === 'post') {
        $user_id = $_SESSION['user']['id'];
        $category = $this->io->post('category') ?? null;
        $font_family = $this->io->post('font_family') ?? null;

        // Validate font
        if ($font_family && !in_array($font_family, $this->allowedFonts)) {
            $font_family = null;
        }

        if (!$category) {
            $_SESSION['error_message'] = 'Please select a category before posting.';
            redirect('/posts/create');
            return;
        }

        $content = $this->io->post('content') ?? '';

        $media_paths = [];

        /* ---------------------------------------------------------
           MEDIA UPLOAD — SECURE & ROBUST
        ----------------------------------------------------------*/
        if (!empty($_FILES['media']['name'][0])) {
            $files = $_FILES['media'];
            $is_multiple = is_array($files['name']);
            $file_count = $is_multiple ? count($files['name']) : 1;

            if ($file_count > 5) {
                $_SESSION['error_message'] = 'Maximum 5 files allowed per post.';
                redirect('/posts/create');
                return;
            }

            $allowed_images = ['jpg','jpeg','png','gif','webp','jfif','bmp'];
            $allowed_videos = ['mp4','webm','ogg','mov','avi','mkv','flv'];
            $allowed_types = array_merge($allowed_images, $allowed_videos);

            for ($i = 0; $i < $file_count; $i++) {
                $file_name  = $is_multiple ? $files['name'][$i] : $files['name'];
                $file_tmp   = $is_multiple ? $files['tmp_name'][$i] : $files['tmp_name'];
                $file_size  = $is_multiple ? $files['size'][$i] : $files['size'];
                $file_error = $is_multiple ? $files['error'][$i] : $files['error'];

                // Skip empty or errored files
                if ($file_error !== UPLOAD_ERR_OK || empty($file_name) || $file_error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_types)) {
                    continue;
                }

                if ($file_size > 50 * 1024 * 1024) {
                    continue;
                }

                // Define upload directory
                $upload_dir = in_array($file_ext, $allowed_images)
                    ? 'public/uploads/images/'
                    : 'public/uploads/videos/';

                // Create directory if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate unique filename
                $unique_name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                $target_path = $upload_dir . $unique_name;

                // Move uploaded file
               // In the file upload section, modify the path handling:
if (move_uploaded_file($file_tmp, $target_path)) {
    // Store the full path including 'public/' in the database
    $media_paths[] = $target_path;  // Keep 'public/' in the stored path
} else {
    error_log("Failed to move uploaded file: $file_name to $target_path");
}
            }
        }

        // Change this validation:
$media_paths = array_values(array_filter($media_paths, function($path) {
    return !empty($path) && file_exists($path);  // Check if file actually exists
}));

        // Encode as clean JSON
        $media_path_json = json_encode($media_paths, JSON_UNESCAPED_SLASHES);

        // Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON encode error for media_path: ' . json_last_error_msg());
            $media_path_json = '[]';
        }

        // --- LOCATION ---
        $latitude = $this->io->post('latitude') ?? null;
        $longitude = $this->io->post('longitude') ?? null;
        $city = null;
        $country = null;

        if ($latitude && $longitude) {
            $locationData = reverse_geocode($latitude, $longitude);
            if (!empty($locationData['address'])) {
                $city = $locationData['address']['city']
                     ?? $locationData['address']['town']
                     ?? $locationData['address']['village']
                     ?? null;
                $country = $locationData['address']['country'] ?? null;
            }
        }

        // --- FINAL DATA FOR INSERT ---
        $data = [
            'user_id'     => $user_id,
            'category'    => $category,
            'content'     => $content,
            'media_path'  => $media_path_json,  // Clean, validated JSON
            'font_family' => $font_family,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'city'        => $city,
            'country'     => $country,
            'created_at'  => current_manila_datetime()
        ];

        // Optional: Debug log the final data
        error_log("Inserting post with media_path: " . $data['media_path']);

        $insert_result = $this->UsersModel->add_post($data);

        if ($insert_result) {
            redirect('/users/user-page');
            return;
        }

        $_SESSION['error_message'] = 'Something went wrong while saving your post.';
        redirect('/posts/create');
        return;
    }

    $_SESSION['error_message'] = 'Invalid request method.';
    redirect('/posts/create');
}

public function edit_post($id) 
{
    $this->call->model('UsersModel');

    // Require login
    if (!isset($_SESSION['user'])) {
        $_SESSION['error_message'] = 'You must be logged in.';
        redirect('/');
        return;
    }

    $logged_in_user = $_SESSION['user'];

    // Get post
    $post = $this->UsersModel->get_post_by_id($id);
    if (!$post) {
        $_SESSION['error_message'] = 'Post not found.';
        redirect('/users/user-page');
        return;
    }

    // Authorization
    if ($logged_in_user['role'] !== 'admin' && $post['user_id'] != $logged_in_user['id']) {
        $_SESSION['error_message'] = 'Unauthorized access.';
        redirect('/users/user-page');
        return;
    }

    // --- GET REQUEST: show edit form ---
    if ($this->io->method() === 'get') {
        $error = $_SESSION['error_message'] ?? null;
        $success = $_SESSION['success_message'] ?? null;
        unset($_SESSION['error_message'], $_SESSION['success_message']);

        return $this->call->view('pages/EditPost', [
            'post' => $post,
            'user' => $logged_in_user,
            'error' => $error,
            'success' => $success,
            'unreadCount' => $this->UsersModel->get_unread_notifications_count($logged_in_user['id']),
            'notifications' => $this->UsersModel->get_notifications($logged_in_user['id'], 5) // Get last 5 notifications
        ]);
    }

    // --- POST REQUEST: handle update ---
    if ($this->io->method() === 'post') {
        $category = $this->io->post('category') ?? null;
        $content = $this->io->post('content') ?? '';

        // Font validation
        $font_family = $this->io->post('font_family') ?? null;
        if ($font_family && !in_array($font_family, $this->allowedFonts)) {
            $font_family = null;
        }

        $removed_media = [];
if (isset($_POST['removed_media'])) {
    $removed_media = $_POST['removed_media'];
    if (!is_array($removed_media)) {
        $removed_media = [$removed_media];
    }
}


        // Load old media
        $old_media = [];
        if (!empty($post['media_path'])) {
            $decoded = json_decode($post['media_path'], true);
            $old_media = is_array($decoded) ? $decoded : [];
        }

if (!empty($removed_media)) {
    foreach ($removed_media as $file) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file, '/');
        if (file_exists($full_path)) @unlink($full_path);
    }

    $old_media = array_filter($old_media, fn($m) => !in_array($m, $removed_media));
    $old_media = array_values($old_media);
}



        // Handle new media upload
        $new_media_paths = [];
        if (!empty($_FILES['media']['name'][0])) {
            $files = $_FILES['media'];
            $is_multiple = is_array($files['name']);
            $file_count = $is_multiple ? count($files['name']) : 1;

            if (($file_count + count($old_media)) > 5) {
                $_SESSION['error_message'] = 'Maximum 5 files allowed per post.';
                redirect("/posts/edit/$id");
                return;
            }

            $allowed_images = ['jpg','jpeg','png','gif','webp','jfif','bmp'];
            $allowed_videos = ['mp4','webm','ogg','mov','avi','mkv','flv'];
            $allowed_types = array_merge($allowed_images, $allowed_videos);

            for ($i=0; $i<$file_count; $i++) {
                $file_name  = $is_multiple ? $files['name'][$i] : $files['name'];
                $file_tmp   = $is_multiple ? $files['tmp_name'][$i] : $files['tmp_name'];
                $file_size  = $is_multiple ? $files['size'][$i] : $files['size'];
                $file_error = $is_multiple ? $files['error'][$i] : $files['error'];

                if ($file_error !== UPLOAD_ERR_OK || empty($file_name)) continue;

                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($file_ext, $allowed_types) || $file_size > 50 * 1024 * 1024) continue;

                $upload_dir = in_array($file_ext, $allowed_images) ? 'public/uploads/images/' : 'public/uploads/videos/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                $unique_name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file_name));
                $target_path = $upload_dir . $unique_name;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $new_media_paths[] = $target_path;
                }
            }
        }

        // Merge old media and new uploads
        $all_media = array_values(array_merge($old_media, $new_media_paths));
        $media_path_json = !empty($all_media) ? json_encode($all_media, JSON_UNESCAPED_SLASHES) : null;

        // Update post
        $data = [
            'category' => $category,
            'content' => $content,
            'media_path' => $media_path_json,
            'font_family' => $font_family
        ];

        if ($this->UsersModel->update_post($id, $data)) {
            $_SESSION['success_message'] = 'Post updated successfully!';
            redirect('/users/user-page');
            return;
        }

        $_SESSION['error_message'] = 'Failed to update post.';
        redirect("/posts/edit/$id");
        return;
    }

    // Invalid request method
    $_SESSION['error_message'] = 'Invalid request method.';
    redirect('/users/user-page');
}

public function delete_post($id)
{
    $this->call->model('UsersModel');

    // Check if AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        } else {
            $_SESSION['error'] = 'Not logged in';
            header('Location: /users/user-page');
            exit;
        }
    }

    $logged_in_user = $_SESSION['user'];

    // Get post by ID
    $post = $this->UsersModel->get_post_by_id($id);
    if (!$post) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        } else {
            $_SESSION['error'] = 'Post not found';
            header('Location: /users/user-page');
            exit;
        }
    }

    // Only admin or owner can delete
    if ($logged_in_user['role'] !== 'admin' && $post['user_id'] != $logged_in_user['id']) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        } else {
            $_SESSION['error'] = 'Unauthorized';
            header('Location: /users/user-page');
            exit;
        }
    }

    // Delete media files if exist
    if (!empty($post['media_path'])) {
        $media_files = json_decode($post['media_path'], true) ?: [$post['media_path']];
        foreach ($media_files as $media) {
            if (file_exists($media)) {
                @unlink($media);
            }
        }
    }

    // Delete post from database
    if ($this->UsersModel->delete_post($id)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Post deleted successfully!', 'redirect' => '/users/user-page']);
            exit;
        } else {
            $_SESSION['success'] = 'Post deleted successfully!';
            header('Location: /users/user-page');
            exit;
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
            exit;
        } else {
            $_SESSION['error'] = 'Failed to delete post';
            header('Location: /users/user-page');
            exit;
        }
    }
}
public function categories()
{
    if (!isset($_SESSION['user'])) {
        redirect('/'); // send to login if not logged in
        return;
    }

    $this->call->model('UsersModel');
    $logged_in_user = $_SESSION['user'];
    $user_id = $logged_in_user['id'];
    $unread_notifications = $this->UsersModel->get_unread_notifications_count($user_id);

    $categories = ['Food', 'Travel', 'Technology', 'Lifestyle'];

    $data = [
        'user' => $logged_in_user,
        'unreadCount' => $unread_notifications,
        'categories' => $categories
    ];

    $this->call->view('/pages/Categories', $data);
}

public function filter_by_category($category)
{
    if (!isset($_SESSION['user'])) {
        redirect('/'); // redirect to login if not logged in
        return;
    }

    $this->call->model('UsersModel');

    $logged_in_user = $_SESSION['user'];
    $user_id = $logged_in_user['id'];

    // Validate category
    $valid_categories = ['Food', 'Travel', 'Technology', 'Lifestyle'];
    if (!in_array($category, $valid_categories)) {
        redirect('/categories'); // redirect to categories page if invalid
        return;
    }

    // Fetch posts by category
    $posts = $this->UsersModel->get_posts_by_category($category);

    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $post['is_liked'] = $this->UsersModel->is_post_liked($post['post_id'], $user_id);
            $post['like_count'] = $this->UsersModel->get_like_count($post['post_id']);
            $post['comments'] = $this->UsersModel->get_comments_by_post($post['post_id']);

            if (!empty($post['comments'])) {
                foreach ($post['comments'] as &$comment) {
                    $comment['replies'] = $this->UsersModel->get_replies_by_comment($comment['comment_id']);
                }
            }
        }
    }

    $unread_notifications = $this->UsersModel->get_unread_notifications_count($user_id);

    $data = [
        'user' => $logged_in_user,
        'posts' => $posts ?: [],
        'unreadCount' => $unread_notifications,
        'selectedCategory' => $category
    ];

    $this->call->view('/pages/UserPage', $data);
}


// Toggle like
public function toggle_like($id) {
    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) redirect('/login');

    $post = $this->UsersModel->get_post_by_id($id);
    if (!$post) redirect('/');

    // Check if this is a like or unlike
    $is_liked = $this->UsersModel->is_post_liked($id, $user_id);
    $this->UsersModel->like_post($id, $user_id);

    // Only create notification for new likes, not unlikes
    if (!$is_liked && $post['user_id'] != $user_id) {
        $notification_data = [
            'user_id' => $post['user_id'], // Notify the post owner
            'actor_id' => $user_id,        // The user who liked
            'post_id' => $id,              // The post that was liked
            'type' => 'like',
            'message' => 'liked your post: ' . (strlen($post['content']) > 30 ? substr($post['content'], 0, 30) . '...' : $post['content']),
            'created_at' => current_manila_datetime()
        ];
        $this->UsersModel->create_notification($notification_data);
    }

    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// Add comment
public function add_comment($id) {
    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) redirect('/login');

    $content = trim($this->io->post('content'));
    if (!$content) redirect($_SERVER['HTTP_REFERER'] ?? '/');

    $post = $this->UsersModel->get_post_by_id($id);
    if (!$post) redirect('/');

    $comment_data = [
        'post_id' => $id,
        'user_id' => $user_id,
        'content' => $content,
        'created_at' => current_manila_datetime()
    ];
    
    $comment_id = $this->UsersModel->add_comment($comment_data);
    
    // Only create notification if the commenter is not the post owner
    if ($post['user_id'] != $user_id) {
        $notification_data = [
            'user_id' => $post['user_id'], // Notify the post owner
            'actor_id' => $user_id,        // The user who commented
            'post_id' => $id,              // The post that was commented on
            'comment_id' => $comment_id,   // The new comment
            'type' => 'comment',
            'message' => 'commented on your post: ' . (strlen($content) > 30 ? substr($content, 0, 30) . '...' : $content),
            'created_at' => current_manila_datetime()
        ];
        $this->UsersModel->create_notification($notification_data);
    }
    
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// Delete comment
public function delete_comment($id) {
    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) redirect('/login');

    $comment = $this->UsersModel->get_comment_by_id($id);
    if ($comment && ($comment['user_id'] == $user_id || $_SESSION['user']['role'] === 'admin')) {
        $this->UsersModel->delete_comment($id);
    }
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// Add reply
public function add_reply($id) {
    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) redirect('/login');

    $content = trim($this->io->post('content'));
    if (!$content) redirect($_SERVER['HTTP_REFERER'] ?? '/');

    // Get the comment being replied to
    $comment = $this->UsersModel->get_comment_by_id($id);
    if (!$comment) redirect('/');

    // Get the post for this comment
    $post = $this->UsersModel->get_post_by_id($comment['post_id']);
    if (!$post) redirect('/');

    $reply_data = [
        'comment_id' => $id,
        'user_id' => $user_id,
        'content' => $content,
        'created_at' => current_manila_datetime()
    ];
    
    $reply_id = $this->UsersModel->add_reply($reply_data);
    
    // Notify the comment author (if they're not the one replying)
    if ($comment['user_id'] != $user_id) {
        $notification_data = [
            'user_id' => $comment['user_id'], // Notify the comment author
            'actor_id' => $user_id,           // The user who replied
            'post_id' => $post['post_id'],    // The post where this happened
            'comment_id' => $id,              // The original comment
            'reply_id' => $reply_id,          // The new reply
            'type' => 'reply',
            'message' => 'replied to your comment: ' . (strlen($content) > 30 ? substr($content, 0, 30) . '...' : $content),
            'created_at' => current_manila_datetime()
        ];
        $this->UsersModel->create_notification($notification_data);
    }
    
    // Also notify the post owner if they're different from the comment author and the one replying
    if ($post['user_id'] != $user_id && $post['user_id'] != $comment['user_id']) {
        $notification_data = [
            'user_id' => $post['user_id'],    // Notify the post owner
            'actor_id' => $user_id,           // The user who replied
            'post_id' => $post['post_id'],    // The post where this happened
            'comment_id' => $id,              // The original comment
            'reply_id' => $reply_id,          // The new reply
            'type' => 'reply',
            'message' => 'replied to a comment on your post: ' . (strlen($content) > 20 ? substr($content, 0, 20) . '...' : $content),
            'created_at' => current_manila_datetime()
        ];
        $this->UsersModel->create_notification($notification_data);
    }
    
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// Delete reply
public function delete_reply($id) {
    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) redirect('/login');

    // Fetch the reply directly using Lavalust DB
    $this->call->database();
    $reply = $this->db->table('replies')->where('reply_id', $id)->get();

    if (!$reply) {
        // Reply not found
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    // Check ownership or admin
    if ($reply['user_id'] == $user_id || $_SESSION['user']['role'] === 'admin') {
        $this->UsersModel->delete_reply($id);
    }

    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// ========== NOTIFICATIONS ENDPOINTS ==========
public function get_notifications()
{
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }

    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'];
    $notifications = $this->UsersModel->get_notifications($user_id);
    $unread_count = $this->UsersModel->get_unread_notifications_count($user_id);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications ?: [],
        'unread_count' => $unread_count
    ]);
}

public function mark_notification_read() {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }

    $user_id = $_SESSION['user']['id'];
    $this->call->model('UsersModel');

    $mark_all = $this->io->post('mark_all');
    if ($mark_all == '1') {
        // Mark all notifications as read
        $this->UsersModel->mark_all_notifications_as_read($user_id);

        // Update session data
        $_SESSION['unread_count'] = 0;

        // Refresh notifications in session
        $notifications = $this->UsersModel->get_notifications($user_id, 5);
        $_SESSION['notifications'] = $notifications;

        echo json_encode(['success' => true]);
        return;
    }

    $notification_id = $this->io->post('notification_id');
    if (!$notification_id) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        return;
    }

    // Verify the notification belongs to the user
    $notification = $this->UsersModel->get_notification_by_id($notification_id);
    if (!$notification || $notification['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    // Mark as read
    $this->UsersModel->mark_notification_as_read($notification_id);

    // Update session data
    if (isset($_SESSION['unread_count']) && $_SESSION['unread_count'] > 0) {
        $_SESSION['unread_count']--;
    }

    // Refresh notifications in session to reflect the change
    $notifications = $this->UsersModel->get_notifications($user_id, 5);
    $_SESSION['notifications'] = $notifications;

    echo json_encode(['success' => true]);
}
public function notifications_page()
{
    // Redirect if not logged in
    if (!isset($_SESSION['user'])) {
        redirect('/');
        return;
    }

    $this->call->model('UsersModel');

    $user_id = $_SESSION['user']['id'];
    $logged_in_user = $_SESSION['user'];

    // Fetch notifications
    $notifications = $this->UsersModel->get_notifications($user_id, 50);
    $unread_count = $this->UsersModel->get_unread_notifications_count($user_id);

    // Render LavaLust view (NO JSON)
    return $this->call->view('/pages/Notifications', [
        'logged_in_user' => $logged_in_user,
        'notifications' => $notifications ?: [],
        'unread_count' => $unread_count
    ]);
}


// ========== PROFILE METHODS ==========
public function profile()
{
    if (!isset($_SESSION['user'])) {
        redirect('/');
        return;
    }

    $this->call->model('UsersModel');

    $user_id = $_SESSION['user']['id'];
    $logged_in_user = $_SESSION['user'];

    $user = $this->UsersModel->get_user_by_id($user_id);
    if (!$user) {
        redirect('/');
        return;
    }

    $unread_notifications = $this->UsersModel->get_unread_notifications_count($user_id);

    $data = [
        'user' => $user,
        'logged_in_user' => $logged_in_user,
        'unread_notifications' => $unread_notifications
    ];

    $this->call->view('/pages/Profile', $data);
}


public function update_profile()
{
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }

    $this->call->model('UsersModel');
    $user_id = $_SESSION['user']['id'];
    $logged_in_user = $_SESSION['user'];

    if ($this->io->method() === 'post') {
        $username = $this->io->post('username');
        $email = $this->io->post('email');

        $user = $this->UsersModel->get_user_by_id($user_id);

        if (!$this->UsersModel->is_username_unique($username, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose a different username.']);
            return;
        }

        $data = [
            'username' => $username,
            'email' => $email
        ];

        if ($this->UsersModel->update($user_id, $data)) {
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'user' => $_SESSION['user']
            ]);
            return;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
            return;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
}

// ========== ADMIN METHODS ==========
public function admin_members_page()
{
    // Ensure session is started and require login
    $this->ensureSession();
    if (!isset($_SESSION['user'])) {
        redirect('/');
        return;
    }

    // Only allow admins to view this page
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        // Redirect non-admins away
        redirect('/users/user-page');
        return;
    }

    $this->call->model('UsersModel');
    $unread = $this->UsersModel->get_unread_notifications_count($_SESSION['user']['id']);

    // Pagination and search input from query params
    $page = (int) ($this->io->get('page') ?? 1);
    $per_page = (int) ($this->io->get('per_page') ?? 10);
    $q = trim($this->io->get('q') ?? '');

    if ($page < 1) $page = 1;
    if ($per_page < 1) $per_page = 10;

    $result = $this->UsersModel->page($q, $per_page, $page);
    $users = $result['records'] ?? [];
    $pagination = [
        'page' => $page,
        'per_page' => $per_page,
        'total_rows' => $result['total_rows'] ?? count($users),
        'total_pages' => max(1, (int) ceil(($result['total_rows'] ?? count($users)) / $per_page))
    ];

    $flash_error = $_SESSION['error_message'] ?? null;
    $flash_success = $_SESSION['success_message'] ?? null;

    // clear flashes after reading
    unset($_SESSION['error_message'], $_SESSION['success_message']);

    return $this->call->view('pages/Members', [
        'user' => $_SESSION['user'],
        'unreadCount' => $unread,
        'notifications' => $this->UsersModel->get_notifications($_SESSION['user']['id'], 5),
        'users' => $users,
        'pagination' => $pagination,
        'search' => $q,
        'error' => $flash_error,
        'success' => $flash_success
    ]);
}

public function admin_create_member_page()
{
    $this->ensureSession();
    if (!isset($_SESSION['user'])) {
        redirect('/');
        return;
    }

    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        redirect('/users/user-page');
        return;
    }

    $this->call->model('UsersModel');
    $unread = $this->UsersModel->get_unread_notifications_count($_SESSION['user']['id']);

    // Handle form submission
    if ($this->io->method() === 'post') {
        $username = trim($this->io->post('username') ?? '');
        $email = trim($this->io->post('email') ?? '');
        $password = $this->io->post('password') ?? '';
        $role = trim($this->io->post('role') ?? 'user');

        $errors = [];
        if ($username === '') {
            $errors[] = 'Username is required.';
        }
        if ($email === '') {
            $errors[] = 'Email is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode(' ', $errors);
            return $this->call->view('pages/MemberCreate', [
                'user' => $_SESSION['user'],
                'unreadCount' => $unread,
                'notifications' => $this->UsersModel->get_notifications($_SESSION['user']['id'], 5),
                'error' => $_SESSION['error_message']
            ]);
        }

        if (!$this->UsersModel->is_username_unique($username)) {
            $_SESSION['error_message'] = 'Username already exists.';
            return $this->call->view('pages/MemberCreate', ['user' => $_SESSION['user'], 'unreadCount'=>$unread, 'notifications'=>$this->UsersModel->get_notifications($_SESSION['user']['id'], 5), 'error'=>$_SESSION['error_message']]);
        }

        if ($this->UsersModel->is_email_exists($email)) {
            $_SESSION['error_message'] = 'Email already exists.';
            return $this->call->view('pages/MemberCreate', ['user' => $_SESSION['user'], 'unreadCount'=>$unread, 'notifications'=>$this->UsersModel->get_notifications($_SESSION['user']['id'], 5), 'error'=>$_SESSION['error_message']]);
        }

        $this->UsersModel->insert([
            'username'=>$username,
            'email'=>$email,
            'password'=>password_hash($password, PASSWORD_BCRYPT),
            'role'=>$role,
            'is_verified'=>1,
            'created_at'=>current_manila_datetime()
        ]);

        $_SESSION['success_message'] = 'User created successfully.';
        redirect('/admin/members');
        return;
    }

    return $this->call->view('pages/MemberCreate', [
        'user' => $_SESSION['user'],
        'unreadCount' => $unread,
        'notifications' => $this->UsersModel->get_notifications($_SESSION['user']['id'], 5),
        'error' => $_SESSION['error_message'] ?? null,
        'success' => $_SESSION['success_message'] ?? null
    ]);
}

public function admin_edit_member_page($id)
{
    $this->ensureSession();
    if (!isset($_SESSION['user'])) {
        redirect('/');
        return;
    }

    $logged = $_SESSION['user'];
    $is_self_edit = ((int) ($logged['id'] ?? 0) === (int) $id) && (isset($_GET['self']) && $_GET['self'] === '1');

    // Allow access if admin OR editing own profile with ?self=1
    if ((($logged['role'] ?? '') !== 'admin') && !$is_self_edit) {
        redirect('/users/user-page');
        return;
    }

    $this->call->model('UsersModel');
    $unread = $this->UsersModel->get_unread_notifications_count($_SESSION['user']['id']);

    $target = $this->UsersModel->get_user_by_id($id);
    if (!$target) {
        $_SESSION['error_message'] = 'User not found.';
        redirect('/admin/members');
        return;
    }

    // Handle POST update
    if ($this->io->method() === 'post') {
        $username = trim($this->io->post('username') ?? '');
        $email = trim($this->io->post('email') ?? '');
        $role = trim($this->io->post('role') ?? '');

        if ($username === '' || $email === '') {
            $_SESSION['error_message'] = 'Username and email are required.';
            return $this->call->view('pages/MemberUpdate', ['user'=>$_SESSION['user'], 'unreadCount'=>$unread, 'notifications'=>$this->UsersModel->get_notifications($_SESSION['user']['id'], 5), 'target'=>$target, 'error'=>$_SESSION['error_message']]);
        }

        if (!$this->UsersModel->is_username_unique($username, $id)) {
            $_SESSION['error_message'] = 'Username already exists.';
            return $this->call->view('pages/MemberUpdate', ['user'=>$_SESSION['user'], 'unreadCount'=>$unread, 'notifications'=>$this->UsersModel->get_notifications($_SESSION['user']['id'], 5), 'target'=>$target, 'error'=>$_SESSION['error_message']]);
        }

        $update_data = ['username'=>$username,'email'=>$email];

        // Only admins can update the role, and not for self-edit
        if (($logged['role'] ?? '') === 'admin' && !$is_self_edit) {
            if ($role === '') {
                $_SESSION['error_message'] = 'Role is required.';
                return $this->call->view('pages/MemberUpdate', ['user'=>$_SESSION['user'], 'unreadCount'=>$unread, 'notifications'=>$this->UsersModel->get_notifications($_SESSION['user']['id'], 5), 'target'=>$target, 'error'=>$_SESSION['error_message']]);
            }
            $update_data['role'] = $role;
        }

        $this->UsersModel->update($id, $update_data);
        $_SESSION['success_message'] = 'User updated successfully.';
        redirect('/admin/members');
        return;
    }

    return $this->call->view('pages/MemberUpdate', [
        'user' => $_SESSION['user'],
        'unreadCount' => $unread,
        'notifications' => $this->UsersModel->get_notifications($_SESSION['user']['id'], 5),
        'target' => $target,
        'error' => $_SESSION['error_message'] ?? null,
        'success' => $_SESSION['success_message'] ?? null,
        'can_edit_role' => (($logged['role'] ?? '') === 'admin')
    ]);
}

public function admin_delete_member($id)
{
    $this->ensureSession();
    $this->requireAdmin();
    $this->call->model('UsersModel');

    $admin = $_SESSION['user'] ?? null;
    if ($admin && (int)$admin['id'] === (int)$id) {
        $_SESSION['error_message'] = 'You cannot delete your own account.';
        redirect('/admin/members');
        return;
    }

    if ($this->UsersModel->delete($id)) {
        $_SESSION['success_message'] = 'User deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete user.';
    }
    redirect('/admin/members');
}

public function search()
{
    header('Content-Type: application/json');

    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }

    $this->call->model('UsersModel');
    $q = trim($this->io->get('q') ?? '');

    if ($q === '') {
        echo json_encode(['success' => true, 'users' => [], 'posts' => []]);
        return;
    }

    // Search users by username only
    $user_results = $this->UsersModel->db->table('users')
        ->like('username', '%'.$q.'%')
        ->get_all();

    // Search posts by category OR by author username
    $post_results = $this->UsersModel->db->table('posts')
        ->join('users', 'users.id = posts.user_id')
        ->group_start() // open parentheses for OR condition
            ->like('posts.category', '%'.$q.'%')
            ->or_like('users.username', '%'.$q.'%')
        ->group_end() // close parentheses
        ->order_by('posts.created_at', 'DESC')
        ->get_all();

    echo json_encode([
        'success' => true,
        'query' => $q,
        'users' => $user_results ?: [],
        'posts' => $post_results ?: []
    ]);
}

}
