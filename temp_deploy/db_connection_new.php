<?php
// VPS Database Connection using SQLite (simpler than MySQL)
try {
     = __DIR__ . /hotel_booking.db;
     = new PDO(sqlite: . );
    ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create basic tables if they don \t exist
