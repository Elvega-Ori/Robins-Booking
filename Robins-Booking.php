<?php
/*
Plugin Name: Hotel Booking
Description: Плагин для бронирования номеров в отеле.
Version: 0.01
Author: Vega Ori
GitHub Plugin URI: https://github.com/Elvega-Ori/Robins-Booking
*/

if (!defined('ABSPATH')) {
    exit;
}

// Регистрация меню в админке
function hb_register_menu() {
    add_menu_page('Robins-Booking', 'Robins-Booking', 'manage_options', 'hb_admin', 'hb_admin_page', 'dashicons-calendar-alt', 6);
}
add_action('admin_menu', 'hb_register_menu');

// Админская страница
function hb_admin_page() {
    echo '<h1>Управление номерами</h1>';
    echo '<a href="?page=hb_admin&action=add_room">Добавить номер</a>';
    
    if (isset($_GET['action']) && $_GET['action'] == 'add_room') {
        hb_add_room_page();
    } else {
        hb_list_rooms();
    }
}

// Страница добавления номера
function hb_add_room_page() {
    if ($_POST['submit']) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'hotel_rooms',
            array(
                'name' => sanitize_text_field($_POST['name']),
                'price' => floatval($_POST['price']),
                'description' => sanitize_textarea_field($_POST['description']),
            )
        );
        echo '<p>Номер добавлен!</p>';
    }
    
    echo '<form method="post">
        <label>Название номера</label>
        <input type="text" name="name" required><br>
        <label>Цена за ночь</label>
        <input type="number" name="price" required><br>
        <label>Описание</label>
        <textarea name="description"></textarea><br>
        <input type="submit" name="submit" value="Добавить номер">
    </form>';
}

// Список номеров
function hb_list_rooms() {
    global $wpdb;
    $rooms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hotel_rooms");
    
    if ($rooms) {
        echo '<table><tr><th>Номер</th><th>Цена</th><th>Описание</th></tr>';
        foreach ($rooms as $room) {
            echo "<tr><td>{$room->name}</td><td>{$room->price}</td><td>{$room->description}</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>Номера не найдены.</p>';
    }
}

// Создание таблицы в БД при активации плагина
function hb_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hotel_rooms';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        price float NOT NULL,
        description text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'hb_create_tables');

// Форма бронирования для отображения на сайте
function hb_booking_form() {
    global $wpdb;
    $rooms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hotel_rooms");

    echo '<form method="post">';
    echo '<label>Выберите номер:</label>';
    echo '<select name="room_id">';
    foreach ($rooms as $room) {
        echo "<option value='{$room->id}'>{$room->name} - {$room->price} руб.</option>";
    }
    echo '</select><br>';

    echo '<label>Дата заезда:</label>';
    echo '<input type="date" name="checkin_date" required><br>';
    echo '<label>Дата выезда:</label>';
    echo '<input type="date" name="checkout_date" required><br>';
    echo '<input type="submit" value="Забронировать">';
    echo '</form>';
}
add_shortcode('hotel_booking_form', 'hb_booking_form');

// Календарь доступности (простой пример)
function hb_check_availability($room_id, $checkin_date, $checkout_date) {
    global $wpdb;
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hotel_bookings WHERE room_id = %d AND (%s BETWEEN checkin_date AND checkout_date OR %s BETWEEN checkin_date AND checkout_date)",
        $room_id, $checkin_date, $checkout_date
    ));
    
    return empty($bookings);
}

// Создание таблицы для бронирований
function hb_create_booking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hotel_bookings';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        room_id mediumint(9) NOT NULL,
        checkin_date date NOT NULL,
        checkout_date date NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'hb_create_booking_table');
?>
