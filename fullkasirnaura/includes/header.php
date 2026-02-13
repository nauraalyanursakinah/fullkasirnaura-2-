<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naura Cofe - Kasir Modern</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hover-scale {
            transition: transform 0.2s;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .text-naura-blue {
            color: #4F46E5;
        }
        
        .bg-naura-blue {
            background-color: #4F46E5;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .print-only, .print-only * {
                visibility: visible;
            }
            .print-only {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-50">