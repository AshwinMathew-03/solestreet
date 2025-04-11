<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Get cart count for notification
$cartCount = 0;
try {
    $cartCountSql = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
    $cartStmt = $conn->prepare($cartCountSql);
    $cartStmt->bind_param("i", $_SESSION['user_id']);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    
    if ($cartResult->num_rows > 0) {
        $cartCount = $cartResult->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    error_log("Error fetching cart count: " . $e->getMessage());
}

// Fetch orders for the current user
try {
    $ordersSql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
    $ordersStmt = $conn->prepare($ordersSql);
    $ordersStmt->bind_param("i", $_SESSION['user_id']);
    $ordersStmt->execute();
    $orders = $ordersStmt->get_result();
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Orders Page Styles */
        .orders-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            flex-wrap: wrap;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            animation: fadeIn 0.8s ease-out;
        }

        .orders-title {
            font-size: 30px;
            font-weight: 700;
            color: #222;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 5px;
        }
        
        .orders-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--hover));
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        .orders-header:hover .orders-title:after {
            width: 100px;
        }
        
        /* Enhanced Professional Toolbar Styles */
        .orders-toolbar {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            padding: 20px 25px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: space-between;
            align-items: center;
            border: none;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .orders-toolbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #1a237e, #3d5afe);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        
        .orders-toolbar:hover {
            box-shadow: 0 12px 25px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .toolbar-left {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .toolbar-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-btn {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #f8f9fa;
            color: #555;
            border: 1px solid rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        
        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s cubic-bezier(0.65, 0, 0.35, 1);
        }
        
        .filter-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .filter-btn:hover::before {
            transform: translateX(100%);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #1a237e, #3d5afe);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 15px rgba(61, 90, 254, 0.2);
        }
        
        .filter-btn.active:hover {
            background: linear-gradient(135deg, #3d5afe, #1a237e);
            box-shadow: 0 8px 20px rgba(61, 90, 254, 0.25);
            transform: translateY(-3px);
        }
        
        .filter-btn i {
            font-size: 14px;
            transition: transform 0.2s ease;
        }
        
        .filter-btn:hover i {
            transform: scale(1.2);
        }
        
        /* Enhanced Search styling */
        .order-search {
            position: relative;
            width: 250px;
        }
        
        .order-search input {
            padding: 12px 20px 12px 45px;
            border-radius: 8px;
            border: 1px solid #e8e8e8;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s;
            color: #333;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            background-color: #f8f9fa;
        }
        
        .order-search input:focus {
            outline: none;
            width: 280px;
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
            background-color: white;
        }
        
        .order-search input::placeholder {
            color: #999;
            transition: all 0.3s ease;
        }
        
        .order-search input:focus::placeholder {
            opacity: 0.7;
            transform: translateX(5px);
        }
        
        .order-search i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .order-search input:focus + i {
            color: #1a237e;
            transform: translateY(-50%) scale(1.1);
        }
        
        .toolbar-separator {
            height: 30px;
            width: 1px;
            background: linear-gradient(to bottom, #e8e8e8, #f5f5f5, #e8e8e8);
            margin: 0 8px;
        }
        
        /* Enhanced View toggle styling */
        .view-toggle {
            display: flex;
            background-color: #f0f0f0;
            border-radius: 8px;
            padding: 3px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .view-toggle:hover {
            box-shadow: inset 0 1px 5px rgba(0,0,0,0.08);
        }
        
        .view-toggle button {
            background: none;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            color: #666;
            font-size: 13px;
            font-weight: 500;
            min-width: 65px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            z-index: 2;
        }
        
        .view-toggle button.active {
            background-color: white;
            color: #1a237e;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            font-weight: 600;
        }
        
        .view-toggle button:not(.active):hover {
            color: #333;
            transform: scale(1.05);
        }
        
        /* Enhanced Sort dropdown styling */
        select#orderSort {
            appearance: none;
            padding: 12px 40px 12px 16px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            min-width: 170px;
        }
        
        select#orderSort:focus {
            outline: none;
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
            background-color: white;
        }
        
        select#orderSort:hover {
            border-color: #ccc;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .orders-toolbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }
            
            .toolbar-left, .toolbar-right {
                width: 100%;
            }
            
            .toolbar-left {
                overflow-x: auto;
                padding-bottom: 5px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
                white-space: nowrap;
                flex-wrap: nowrap;
            }
            
            .toolbar-left::-webkit-scrollbar {
                display: none;
            }
            
            .toolbar-right {
                margin-top: 10px;
                justify-content: space-between;
            }
            
            .order-search {
                width: 100%;
            }
            
            .order-search input, .order-search input:focus {
                width: 100%;
            }
            
            select#orderSort {
                flex: 1;
            }
        }

        /* Original Styles - Keep everything below */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* List View Styles */
        .orders-list.list-view {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
        }
        
        .orders-list.list-view .order-card {
            display: table-row;
            box-shadow: none;
            transition: none;
            transform: none !important;
        }
        
        .orders-list.list-view .order-card > div {
            display: table-cell;
            vertical-align: middle;
            padding: 15px;
            background-color: white;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .orders-list.list-view .order-card > div:first-child {
            border-left: 1px solid #f0f0f0;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .orders-list.list-view .order-card > div:last-child {
            border-right: 1px solid #f0f0f0;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .orders-list.list-view .order-header {
            width: 15%;
            background-color: white;
            padding: 15px;
            border-bottom: none;
            display: table-cell;
        }
        
        .orders-list.list-view .order-content {
            display: table-cell;
            padding: 0;
        }
        
        .orders-list.list-view .order-details {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
            align-items: center;
        }
        
        .orders-list.list-view .detail-group {
            flex-direction: row;
            align-items: center;
            gap: 8px;
        }
        
        .orders-list.list-view .detail-label {
            margin-bottom: 0;
            font-size: 11px;
        }
        
        .orders-list.list-view .order-items {
            display: none;
        }
        
        .orders-list.list-view .order-actions {
            display: table-cell;
            width: 220px;
            text-align: right;
            margin-top: 0;
            padding: 15px;
            white-space: nowrap;
        }
        
        .orders-list.list-view .order-card:hover {
            transform: none;
            box-shadow: none;
        }
        
        .orders-list.list-view .order-card:hover > div {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: #fafafa;
        }

        .order-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            position: relative;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .order-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--hover));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .order-card:hover::after {
            opacity: 1;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-id {
            font-weight: 600;
            color: #222;
            font-size: 15px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .order-date i {
            color: #999;
            font-size: 13px;
        }

        .order-content {
            padding: 20px 25px;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .detail-label i {
            font-size: 12px;
            color: #aaa;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .status-pending {
            background-color: #fff8e1;
            color: #f57c00;
        }

        .status-processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-delivered {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .status-paid {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .order-items {
            margin-top: 15px;
            border-top: 1px dashed #eee;
            padding-top: 18px;
        }

        .items-header {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .items-header i {
            color: #888;
            font-size: 13px;
        }

        .item-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .item-badge {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #333;
            border: 1px solid #f0f0f0;
            transition: all 0.2s;
        }
        
        .item-badge:hover {
            background-color: white;
            border-color: #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 12px;
        }

        .btn-details, .btn-track, .btn-invoice {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-details {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #eee;
        }

        .btn-track {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid rgba(25, 118, 210, 0.1);
        }

        .btn-invoice {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .btn-details:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .btn-track:hover {
            background-color: #bbdefb;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(25, 118, 210, 0.1);
        }

        .btn-invoice:hover {
            background-color: var(--hover);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(61, 90, 254, 0.2);
        }

        .no-orders {
            text-align: center;
            padding: 50px 20px;
            margin: 30px auto;
            max-width: 500px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .empty-orders-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f17c58, #e94584, #6b5df4, #24c6dc);
            background-size: 300% 300%;
            animation: gradientShift 10s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .empty-orders-icon::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            right: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
        }
        
        .empty-orders-icon i {
            position: relative;
            font-size: 42px;
            background: linear-gradient(135deg, #f17c58, #e94584, #6b5df4, #24c6dc);
            background-size: 300% 300%;
            animation: gradientShift 10s ease infinite;
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            z-index: 2;
        }
        
        .no-orders h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .no-orders p {
            font-size: 16px;
            color: #777;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .no-orders .btn-invoice {
            transition: all 0.3s ease;
            transform-origin: center;
        }
        
        .no-orders .btn-invoice:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0,0,0,0.1);
        }
        
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* No search results state */
        .no-search-results {
            animation: fadeIn 0.5s ease-out;
        }

        @media (max-width: 768px) {
            .order-header, .order-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-details, .btn-track, .btn-invoice {
                justify-content: center;
            }
            
            /* Mobile responsive toolbar */
            .orders-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-search {
                width: 100%;
                margin-top: 5px;
            }
            
            .order-search input {
                width: 100%;
            }
            
            .orders-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .toolbar-left, .toolbar-right {
                width: 100%;
            }
            
            .toolbar-left {
                overflow-x: auto;
                padding-bottom: 5px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none; /* IE and Edge */
            }
            
            .toolbar-left::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
            
            .toolbar-right {
                margin-top: 10px;
                justify-content: space-between;
            }
            
            .orders-list.list-view {
                display: flex;
                flex-direction: column;
            }
            
            .orders-list.list-view .order-card,
            .orders-list.list-view .order-card > div,
            .orders-list.list-view .order-header,
            .orders-list.list-view .order-content,
            .orders-list.list-view .order-actions {
                display: block;
                width: 100%;
            }
            
            .orders-list.list-view .order-details {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .orders-list.list-view .detail-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .orders-list.list-view .order-actions {
                text-align: left;
                padding-top: 15px;
            }
        }

        /* Professional Top Bar Styles */
        .top-bar {
            background: linear-gradient(to right, #1a237e, #3d5afe);
            padding: 10px 0;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            font-size: 13px;
        }

        .top-contact {
            display: flex;
            align-items: center;
        }

        .top-contact a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            margin-right: 20px;
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .top-contact a:hover {
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
        }

        .top-contact a i {
            margin-right: 8px;
            font-size: 14px;
        }
        
        .top-right {
            display: flex;
            align-items: center;
        }
        
        .top-right .support-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .top-right .support-link:hover {
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
        }
        
        .top-right .support-link i {
            margin-right: 6px;
        }
        
        .top-separator {
            height: 16px;
            width: 1px;
            background-color: rgba(255,255,255,0.3);
            margin: 0 15px;
        }
        
        .top-right .business-hours {
            color: rgba(255,255,255,0.9);
            font-size: 12px;
            display: flex;
            align-items: center;
        }
        
        .top-right .business-hours i {
            margin-right: 6px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .top-contact span {
                display: none;
            }
            
            .top-contact a {
                margin-right: 10px;
                padding: 4px 8px;
            }
            
            .top-right .business-hours span {
                display: none;
            }
            
            .top-separator {
                margin: 0 10px;
            }
        }

        /* Enhanced Main Navigation Styles */
        .main-nav {
            display: flex;
            align-items: center;
        }
        
        .main-nav ul {
            display: flex;
            gap: 5px;
            list-style-type: none;
            margin: 0;
            padding: 0;
        }
        
        .main-nav li {
            position: relative;
        }
        
        .main-nav a {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }
        
        .main-nav a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(to right, #1a237e, #3d5afe);
            border-radius: 3px;
            transform: translateX(-50%);
            transition: width 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            z-index: 1;
        }
        
        .main-nav a::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: rgba(61, 90, 254, 0.08);
            border-radius: 8px;
            opacity: 0;
            transition: opacity 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            z-index: 0;
        }
        
        .main-nav a:hover {
            color: #1a237e;
            transform: translateY(-2px);
        }
        
        .main-nav a:hover::before {
            width: 70%;
        }
        
        .main-nav a:hover::after {
            opacity: 1;
        }
        
        .main-nav a.active {
            color: #1a237e;
            font-weight: 600;
        }
        
        .main-nav a.active::before {
            width: 80%;
            background: linear-gradient(to right, #1a237e, #3d5afe);
            height: 3px;
        }
        
        .main-nav a.active::after {
            opacity: 1;
            background-color: rgba(61, 90, 254, 0.12);
        }
        
        .main-nav a i {
            margin-right: 6px;
            font-size: 16px;
            position: relative;
            transition: transform 0.3s ease;
            z-index: 1;
        }
        
        .main-nav a:hover i {
            transform: translateY(-2px);
        }
        
        /* Mobile menu enhancements */
        @media (max-width: 991px) {
            .main-nav {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: white;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                border-radius: 0 0 12px 12px;
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 0;
                transform: translateY(-10px);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
                z-index: 1000;
                overflow: hidden;
                max-height: 0;
            }
            
            .main-nav.active {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
                max-height: 400px;
            }
            
            .main-nav ul {
                flex-direction: column;
                width: 100%;
                gap: 0;
            }
            
            .main-nav li {
                width: 100%;
            }
            
            .main-nav a {
                padding: 12px 25px;
                width: 100%;
                border-radius: 0;
            }
            
            .main-nav a::before {
                bottom: auto;
                top: 0;
                left: 0;
                transform: none;
                width: 3px;
                height: 0;
                transition: height 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            }
            
            .main-nav a:hover::before,
            .main-nav a.active::before {
                width: 3px;
                height: 100%;
            }
            
            .main-nav a::after {
                border-radius: 0;
            }
        }
        
        /* Mobile menu button enhancements */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #333;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1050;
        }
        
        .mobile-menu-btn:hover {
            color: #1a237e;
            background-color: rgba(61, 90, 254, 0.08);
        }
        
        @media (max-width: 991px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-container {
                position: relative;
            }
        }

        /* Professional Brand and Header Styles */
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            position: relative;
        }
        
        .brand {
            display: flex;
            align-items: center;
        }
        
        .brand h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #1a237e, #3d5afe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            position: relative;
        }
        
        .brand h1 span {
            color: #3d5afe;
            font-weight: 400;
            position: relative;
        }
        
        .brand h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 3px;
            background: linear-gradient(to right, #1a237e, transparent);
        }
        
        .nav-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Enhanced Top Social Links */
        .top-social-links {
            display: flex;
            gap: 12px;
            margin-right: 15px;
        }
        
        .top-social-links a {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            background-color: #f8f9fa;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-size: 14px;
            text-decoration: none;
        }
        
        .top-social-links a:hover {
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .top-social-links a:nth-child(1):hover {
            background-color: #3b5998; /* Facebook */
        }
        
        .top-social-links a:nth-child(2):hover {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); /* Instagram */
        }
        
        .top-social-links a:nth-child(3):hover {
            background-color: #1da1f2; /* Twitter */
        }
        
        .top-social-links a:nth-child(4):hover {
            background-color: #e60023; /* Pinterest */
        }
        
        /* Enhanced User Controls */
        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            text-decoration: none;
            position: relative;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-size: 16px;
        }
        
        .cart-icon:hover {
            background-color: #3d5afe;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(61, 90, 254, 0.2);
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(244, 67, 54, 0.3);
            border: 2px solid white;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 30px;
            background-color: #f8f9fa;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid transparent;
        }
        
        .user-toggle:hover {
            background-color: rgba(61, 90, 254, 0.08);
            color: #1a237e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-color: rgba(61, 90, 254, 0.1);
        }
        
        .user-toggle i {
            font-size: 16px;
            color: #3d5afe;
            transition: transform 0.3s ease;
        }
        
        .user-toggle:hover i {
            transform: scale(1.1);
        }
        
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            z-index: 1000;
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -5px;
            right: 20px;
            width: 10px;
            height: 10px;
            background-color: white;
            transform: rotate(45deg);
            border-top: 1px solid rgba(0,0,0,0.05);
            border-left: 1px solid rgba(0,0,0,0.05);
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            position: relative;
        }
        
        .dropdown-menu a::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: transparent;
            transition: background-color 0.2s;
            z-index: -1;
        }
        
        .dropdown-menu a:hover {
            color: #1a237e;
        }
        
        .dropdown-menu a:hover::after {
            background-color: rgba(61, 90, 254, 0.08);
        }
        
        .dropdown-menu a i {
            font-size: 16px;
            color: #3d5afe;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-menu a.active {
            color: #1a237e;
            font-weight: 600;
        }
        
        .dropdown-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(to bottom, #1a237e, #3d5afe);
            border-radius: 0 3px 3px 0;
        }
        
        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        /* Container styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }
        
        /* Main header effects */
        .main-header {
            background-color: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .main-header.scrolled {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .main-header.scrolled .header-content {
            padding: 10px 20px;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 991px) {
            .nav-container {
                justify-content: flex-end;
                flex: 1;
            }
            
            .top-social-links {
                display: none;
            }
            
            .user-toggle span {
                display: none;
            }
            
            .user-toggle {
                padding: 8px;
                border-radius: 50%;
            }
            
            .user-toggle i {
                margin-right: 0;
            }
            
            .cart-icon, .user-toggle {
                width: 40px;
                height: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .brand h1 {
                font-size: 24px;
            }
            
            .header-content {
                padding: 12px 15px;
            }
            
            .user-controls {
                gap: 10px;
            }
            
            .cart-icon, .user-toggle {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-content">
                <div class="top-contact">
                    <a href="tel:+1234567890"><i class="fas fa-phone-alt"></i> <span>+1 (234) 567-890</span></a>
                    <a href="mailto:info@solestreet.com"><i class="fas fa-envelope"></i> <span>info@solestreet.com</span></a>
                </div>
                <div class="top-right">
                    <a href="contact.php" class="support-link"><i class="fas fa-headset"></i> <span>Support</span></a>
                    <div class="top-separator"></div>
                    <div class="business-hours"><i class="far fa-clock"></i> <span>Mon-Fri: 9AM - 6PM</span></div>
                </div>
            </div>
        </div>
    
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <h1>Sole<span>street</span></h1>
                </div>
                
                <div class="nav-container">
                    <button class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="top-social-links">
                        <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="https://pinterest.com" target="_blank"><i class="fab fa-pinterest-p"></i></a>
                    </div>
                    
                    <nav class="main-nav">
                        <ul>
                            <li><a href="userdashboard.php"><i class="fas fa-home"></i> Home</a></li>
                            <li><a href="products.php"><i class="fas fa-tshirt"></i> Products</a></li>
                            <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                            <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                        </ul>
                    </nav>
                    
                    <div class="user-controls">
                        <a href="cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if($cartCount > 0): ?>
                                <span class="cart-count"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="user-dropdown">
                            <a href="#" class="user-toggle">
                                <i class="fas fa-user"></i>
                                <span><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Account'; ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                                <a href="my_orders.php" class="active"><i class="fas fa-box"></i> My Orders</a>
                                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="orders-container">
        <div class="orders-header">
            <h1 class="orders-title">My Orders</h1>
            <div class="order-search">
                <i class="fas fa-search"></i>
                <input type="text" id="orderSearchInput" placeholder="Search orders..." />
            </div>
        </div>
        
        <div class="orders-toolbar">
            <div class="toolbar-left">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-list"></i> All Orders
                </button>
                <button class="filter-btn" data-filter="pending">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="filter-btn" data-filter="processing">
                    <i class="fas fa-cog"></i> Processing
                </button>
                <button class="filter-btn" data-filter="shipped">
                    <i class="fas fa-truck"></i> Shipped
                </button>
                <button class="filter-btn" data-filter="delivered">
                    <i class="fas fa-check-circle"></i> Delivered
                </button>
            </div>
            <div class="toolbar-right">
                <div class="view-toggle">
                    <button class="active" id="cardView">Card</button>
                    <button id="listView">List</button>
                </div>
                <div class="toolbar-separator"></div>
                <select id="orderSort" class="filter-btn">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="amount-high">Amount: High to Low</option>
                    <option value="amount-low">Amount: Low to High</option>
                </select>
            </div>
        </div>
        
        <?php if (isset($orders) && $orders->num_rows > 0): ?>
            <div class="orders-list">
                <?php while ($order = $orders->fetch_assoc()): 
                    // Fetch order items
                    $itemsSql = "SELECT oi.*, p.name FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?";
                    $itemsStmt = $conn->prepare($itemsSql);
                    $itemsStmt->bind_param("s", $order['order_id']);
                    $itemsStmt->execute();
                    $orderItems = $itemsStmt->get_result();
                    
                    // Count items
                    $itemCount = $orderItems->num_rows;
                ?>
                <div class="order-card" data-status="<?php echo strtolower($order['status']); ?>" data-orderid="<?php echo htmlspecialchars($order['order_id']); ?>" data-amount="<?php echo $order['total_amount']; ?>" data-date="<?php echo strtotime($order['order_date']); ?>">
                    <div class="order-header">
                        <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                        <div class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                        </div>
                    </div>
                    <div class="order-content">
                        <div class="order-details">
                            <div class="detail-group">
                                <div class="detail-label">
                                    <i class="fas fa-tag"></i> Order Status
                                </div>
                                <div class="detail-value">
                                    <?php 
                                    $statusClass = 'status-' . strtolower($order['status']);
                                    $statusIcon = '';
                                    
                                    switch(strtolower($order['status'])) {
                                        case 'pending':
                                            $statusIcon = '<i class="fas fa-clock"></i>';
                                            break;
                                        case 'processing':
                                            $statusIcon = '<i class="fas fa-cog"></i>';
                                            break;
                                        case 'shipped':
                                            $statusIcon = '<i class="fas fa-truck"></i>';
                                            break;
                                        case 'delivered':
                                            $statusIcon = '<i class="fas fa-check-circle"></i>';
                                            break;
                                        case 'cancelled':
                                            $statusIcon = '<i class="fas fa-times-circle"></i>';
                                            break;
                                        case 'paid':
                                            $statusIcon = '<i class="fas fa-money-bill-wave"></i>';
                                            break;
                                        default:
                                            $statusIcon = '<i class="fas fa-circle"></i>';
                                    }
                                    
                                    echo '<span class="order-status ' . $statusClass . '">' . $statusIcon . ' ' . ucfirst($order['status']) . '</span>';
                                    ?>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">
                                    <i class="fas fa-credit-card"></i> Payment Method
                                </div>
                                <div class="detail-value">
                                    <?php 
                                    if ($order['payment_method'] == 'cod') {
                                        echo '<i class="fas fa-money-bill-alt" style="color: #4caf50; margin-right: 5px;"></i> Cash on Delivery';
                                    } else {
                                        echo '<i class="fas fa-credit-card" style="color: #2196f3; margin-right: 5px;"></i> Online Payment';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">
                                    <i class="fas fa-rupee-sign"></i> Order Total
                                </div>
                                <div class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">
                                    <i class="fas fa-map-marker-alt"></i> Shipping Address
                                </div>
                                <div class="detail-value" style="font-size: 13px;">
                                    <?php echo htmlspecialchars(substr($order['address'], 0, 50) . (strlen($order['address']) > 50 ? '...' : '')); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <div class="items-header">
                                <i class="fas fa-shopping-bag"></i> <?php echo $itemCount; ?> items in this order
                            </div>
                            <div class="item-list">
                                <?php 
                                $displayLimit = 3; // Show only a limited number of items
                                $itemCounter = 0;
                                $orderItems->data_seek(0);
                                
                                while ($item = $orderItems->fetch_assoc()): 
                                    if ($itemCounter < $displayLimit):
                                ?>
                                    <span class="item-badge">
                                        <?php echo htmlspecialchars($item['product_name'] ?? $item['name']) . ' × ' . $item['quantity']; ?>
                                    </span>
                                <?php 
                                    endif;
                                    $itemCounter++;
                                endwhile; 
                                
                                // Display the +X more message if needed
                                if ($itemCounter > $displayLimit):
                                ?>
                                    <span class="item-badge">+<?php echo $itemCounter - $displayLimit; ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <a href="view_order_details.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn-details">
                                <i class="fas fa-info-circle"></i> View Details
                            </a>
                            <?php if ($order['status'] == 'shipped' || $order['status'] == 'processing'): ?>
                            <a href="track_order.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn-track">
                                <i class="fas fa-truck"></i> Track Order
                            </a>
                            <?php endif; ?>
                            <a href="view_invoice.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn-invoice">
                                <i class="fas fa-file-invoice"></i> View Invoice
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-orders">
                <div class="empty-orders-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>No Orders Found</h3>
                <p>Looks like you haven't placed any orders yet.<br>Explore our collection and find something you'll love!</p>
                <a href="products.php" class="btn-invoice" style="display: inline-block; margin-top: 25px;">
                    <i class="fas fa-shopping-cart"></i> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header scroll effect
            const header = document.querySelector('.main-header');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
            
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const mainNav = document.querySelector('.main-nav');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    mainNav.classList.toggle('active');
                    
                    // Change icon based on menu state
                    const icon = this.querySelector('i');
                    if (mainNav.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.nav-container') && mainNav.classList.contains('active')) {
                    mainNav.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
            
            // Enhanced Order Cards animations with stagger effect
            const orderCards = document.querySelectorAll('.order-card');
            
            if ('IntersectionObserver' in window && orderCards.length > 0) {
                // Initial setup for animations
                orderCards.forEach((card, index) => {
                    card.style.opacity = 0;
                    card.style.transform = 'translateY(30px)';
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                });
                
                // Create animation observer
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry, idx) => {
                        if (entry.isIntersecting) {
                            // Calculate staggered delay based on card position
                            const delay = 0.1 + (Array.from(orderCards).indexOf(entry.target) * 0.08);
                            entry.target.style.transitionDelay = delay + 's';
                            
                            // Start animation
                            setTimeout(() => {
                                entry.target.style.opacity = 1;
                                entry.target.style.transform = 'translateY(0)';
                            }, 50);
                            
                            // Stop observing after animation
                            observer.unobserve(entry.target);
                        }
                    });
                }, { 
                    threshold: 0.15,
                    rootMargin: '0px 0px -10% 0px'
                });
                
                // Observe each card
                orderCards.forEach(card => {
                    observer.observe(card);
                });
            }
            
            // Button hover effects with scale
            const actionButtons = document.querySelectorAll('.btn-details, .btn-track, .btn-invoice, .filter-btn');
            actionButtons.forEach(btn => {
                btn.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.96)';
                });
                
                btn.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Ripple effect for buttons
            const addRippleEffect = (button) => {
                button.addEventListener('click', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            };
            
            // Add ripple effect to all action buttons
            actionButtons.forEach(addRippleEffect);
            
            // Filter buttons functionality
            const filterBtns = document.querySelectorAll('.filter-btn[data-filter]');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all filter buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    
                    // Add active class to current button
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
                    // Apply filter with animation
                    orderCards.forEach(card => {
                        const status = card.getAttribute('data-status');
                        
                        if (filter === 'all' || status === filter) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = 1;
                                card.style.transform = 'translateY(0)';
                            }, 50);
                        } else {
                            // Fade out
                            card.style.opacity = 0;
                            card.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
            
            // Enhanced search functionality with debounce
            const searchInput = document.getElementById('orderSearchInput');
            if (searchInput) {
                // Add debounce to search for better performance
                let searchTimeout;
                
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    
                    // Add searching indicator
                    if (this.value) {
                        document.querySelector('.orders-list').classList.add('searching');
                    } else {
                        document.querySelector('.orders-list').classList.remove('searching');
                    }
                    
                    searchTimeout = setTimeout(() => {
                        const searchText = this.value.toLowerCase();
                        
                        // Count visible cards for empty state
                        let visibleCount = 0;
                        
                        orderCards.forEach(card => {
                            const orderId = card.getAttribute('data-orderid').toLowerCase();
                            const visible = orderId.includes(searchText);
                            
                            if (visible) {
                                visibleCount++;
                                card.style.display = 'block';
                                setTimeout(() => {
                                    card.style.opacity = 1;
                                    card.style.transform = 'translateY(0)';
                                }, 50);
                            } else {
                                // Fade out
                                card.style.opacity = 0;
                                card.style.transform = 'translateY(20px)';
                                setTimeout(() => {
                                    card.style.display = 'none';
                                }, 300);
                            }
                        });
                        
                        // Show empty state if no results
                        const noResultsEl = document.querySelector('.no-search-results');
                        if (visibleCount === 0 && searchText) {
                            if (!noResultsEl) {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'no-orders no-search-results';
                                emptyState.innerHTML = `
                                    <div class="empty-orders-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h3>No Orders Found</h3>
                                    <p>No orders matched your search "${searchText}"</p>
                                    <button class="btn-invoice clear-search" style="display: inline-block; margin-top: 15px; border: none; cursor: pointer;">
                                        <i class="fas fa-times"></i> Clear Search
                                    </button>
                                `;
                                document.querySelector('.orders-list').after(emptyState);
                                
                                // Add clear search handler
                                document.querySelector('.clear-search').addEventListener('click', () => {
                                    searchInput.value = '';
                                    searchInput.dispatchEvent(new Event('input'));
                                });
                            }
                        } else if (noResultsEl) {
                            noResultsEl.remove();
                        }
                    }, 300);
                });
                
                // Add placeholder animation effect
                searchInput.addEventListener('focus', function() {
                    this.classList.add('active');
                });
                
                searchInput.addEventListener('blur', function() {
                    if (!this.value) {
                        this.classList.remove('active');
                    }
                });
            }
            
            // View toggle functionality with smooth transition
            const cardViewBtn = document.getElementById('cardView');
            const listViewBtn = document.getElementById('listView');
            const ordersList = document.querySelector('.orders-list');
            
            if (cardViewBtn && listViewBtn) {
                cardViewBtn.addEventListener('click', function() {
                    // Add transition class
                    ordersList.classList.add('view-transitioning');
                    
                    // Change active button state
                    cardViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    
                    // Apply view change after brief delay
                    setTimeout(() => {
                        ordersList.classList.remove('list-view');
                        
                        // Remove transition class after animation completes
                        setTimeout(() => {
                            ordersList.classList.remove('view-transitioning');
                        }, 300);
                    }, 50);
                });
                
                listViewBtn.addEventListener('click', function() {
                    // Add transition class
                    ordersList.classList.add('view-transitioning');
                    
                    // Change active button state
                    listViewBtn.classList.add('active');
                    cardViewBtn.classList.remove('active');
                    
                    // Apply view change after brief delay
                    setTimeout(() => {
                        ordersList.classList.add('list-view');
                        
                        // Remove transition class after animation completes
                        setTimeout(() => {
                            ordersList.classList.remove('view-transitioning');
                        }, 300);
                    }, 50);
                });
            }
            
            // Sort functionality with animation
            const sortSelect = document.getElementById('orderSort');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    const sortValue = this.value;
                    const orderCardsArray = Array.from(orderCards);
                    
                    // Add sorting class for animation
                    ordersList.classList.add('sorting');
                    
                    // Sort all cards
                    orderCardsArray.sort((a, b) => {
                        if (sortValue === 'newest') {
                            return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                        } else if (sortValue === 'oldest') {
                            return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
                        } else if (sortValue === 'amount-high') {
                            return parseFloat(b.getAttribute('data-amount')) - parseFloat(a.getAttribute('data-amount'));
                        } else if (sortValue === 'amount-low') {
                            return parseFloat(a.getAttribute('data-amount')) - parseFloat(b.getAttribute('data-amount'));
                        }
                        return 0;
                    });
                    
                    // Animate cards out
                    orderCards.forEach(card => {
                        card.style.opacity = 0;
                        card.style.transform = 'translateY(20px)';
                    });
                    
                    // Wait for fade out
                    setTimeout(() => {
                        // Reappend sorted nodes
                        orderCardsArray.forEach(card => {
                            ordersList.appendChild(card);
                        });
                        
                        // Animate cards back in with staggered delay
                        orderCardsArray.forEach((card, index) => {
                            setTimeout(() => {
                                card.style.opacity = 1;
                                card.style.transform = 'translateY(0)';
                            }, 50 + (index * 50));
                        });
                        
                        // Remove sorting class
                        setTimeout(() => {
                            ordersList.classList.remove('sorting');
                        }, 500);
                    }, 300);
                });
            }
        });
    </script>
    
    <style>
        /* Additional dynamic style effects */
        .ripple-effect {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
            transform: scale(0);
            animation: ripple 0.6s linear;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .btn-details, .btn-track, .btn-invoice, .filter-btn {
            position: relative;
            overflow: hidden;
        }
        
        .orders-list.view-transitioning .order-card {
            transition: all 0.3s ease;
        }
        
        .orders-list.sorting .order-card {
            transition: all 0.3s ease !important;
        }
        
        .orders-list.searching .order-card {
            transition: all 0.3s ease !important;
        }
        
        .order-search input.active {
            background-color: white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
    </style>
</body>
</html> 