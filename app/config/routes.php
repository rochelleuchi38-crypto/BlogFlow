<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/


// Auth routes
$router->match('/', 'UserController::login', ['GET', 'POST']); // Login page as home
$router->match('/auth/register', 'UserController::register', ['GET', 'POST']);
$router->get('/auth/logout', 'UserController::logout');
$router->get('/auth/verify', 'UserController::verify');





$router->match('/auth/verify_code', 'UserController::verify_code', ['GET', 'POST']);
$router->get('/auth/resend', 'UserController::resend_verification');

// User routes
$router->get('/users', 'UserController::index');
$router->get('/users/user-page', 'UserController::user_page');
$router->get('/users/profile', 'UserController::profile');
$router->match('/users/update_profile', 'UserController::update_profile', ['GET', 'POST']);

// Post routes
$router->get('/posts', 'UserController::post_page');
$router->match('/posts/create', 'UserController::create_post', ['GET', 'POST']);
$router->match('/posts/edit/{iad}', 'UserController::edit_post', ['GET', 'POST']);
$router->get('/posts/delete/{id}', 'UserController::delete_post');

// Category routes
$router->get('/categories', 'UserController::categories');
$router->get('/categories/filter/{category}', 'UserController::filter_by_category');

// Like routes
$router->post('/posts/{id}/like', 'UserController::toggle_like');

// Comment routes
$router->post('/posts/{id}/comment', 'UserController::add_comment');
$router->post('/comments/{id}/delete', 'UserController::delete_comment');
$router->post('/comments/{id}/reply', 'UserController::add_reply');
$router->post('/replies/{id}/delete', 'UserController::delete_reply');

// Notification routes
$router->get('/notifications', 'UserController::notifications_page');
$router->post('/notifications/mark_read', 'UserController::mark_notification_read');

// Search
$router->get('/search', 'UserController::search');

// Admin / Members pages (serve the Vue-based pages)
$router->get('/admin/members', 'UserController::admin_members_page');
$router->match('/admin/members/create', 'UserController::admin_create_member_page', ['GET', 'POST']);
$router->match('/admin/members/{id}/edit', 'UserController::admin_edit_member_page', ['GET', 'POST']);
$router->post('/admin/members/{id}/delete', 'UserController::admin_delete_member');

