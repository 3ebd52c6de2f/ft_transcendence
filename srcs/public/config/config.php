<?php

function databaseConnection(): SQLite3 {
    $dbpath = "/tmp/database.sqlite";
    $database = new SQLite3($dbpath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $database->exec("PRAGMA foreign_keys = ON;");
    initDatabaseTables($database);
    return $database;
}
// conexion a db

function initDatabaseTables(&$database) : void {
    $tableSchema = [
        // formato de usuarios
        "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        elo INTEGER DEFAULT 200,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created TEXT DEFAULT CURRENT_TIMESTAMP,
        last_login TEXT );",

        // formato de auth
        "CREATE TABLE IF NOT EXISTS auth (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        expires_at TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);",

        // formato de ajustes
        "CREATE TABLE IF NOT EXISTS user_settings (
        user_id INTEGER PRIMARY KEY,
        language TEXT DEFAULT 'en',
        notifications_enabled  INTEGER DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);",

        // formato de solicitudes de amistad
        "CREATE TABLE IF NOT EXISTS friend_request (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE);",

        // formato de gestion de amigos
        "CREATE TABLE IF NOT EXISTS friends (
        user_id INTEGER NOT NULL,
        friend_id INTEGER NOT NULL,
        PRIMARY KEY (user_id, friend_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE);",

        // formato de gestion de mensajeria
        "CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        sent_at TEXT DEFAULT CURRENT_TIMESTAMP,
        is_read INTEGER DEFAULT 0,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE);",

        // gestion de partidas al pong
        "CREATE TABLE IF NOT EXISTS matches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        creator_id INTEGER NOT NULL,
        opponent_id INTEGER,
        status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT,
        data TEXT,
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (opponent_id) REFERENCES users(id) ON DELETE SET NULL);",

        // ranking
        "CREATE TABLE IF NOT EXISTS leaderboard (
        user_id INTEGER PRIMARY KEY,
        games_played INTEGER DEFAULT 0,
        games_won INTEGER DEFAULT 0,
        points INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);",

        // datos multimedia del usuario
        "CREATE TABLE IF NOT EXISTS user_media (
        user_id INTEGER PRIMARY KEY,
        avatar_path TEXT NOT NULL,
        uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);"
    ];
    foreach ($tableSchema as $sql) {
        if (!$database->exec($sql)) {
            echo "Error al ejecutar: $sql\n";
            echo "SQLite Error: " . $database->lastErrorMsg() . "\n";
        }
    }
}

?>