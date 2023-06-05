<?php
/* Plugin Name: Magnur Trekning
 * Description: Custom Treknings-System for Budbil.no
 * Version: 1.0
 * Author: Einar Magnus Bostad
 * Author URI: www.magnur.no
 */

// Add a custom admin menu item
function magnur_trekning_add_admin_menu() {
    add_menu_page(
        'Magnur Trekning',
        'Trekning (magnur)',
        'manage_options',
        'magnur-trekning-admin',
        'magnur_trekning_admin_page',
        'dashicons-tickets'
    );
}
add_action('admin_menu', 'magnur_trekning_add_admin_menu');

function magnur_trekning_admin_page() {
    ?>
    <div class="admin-wrapper">
        <img id="logo" src="https://bcbud.no/nmkelverum/wp-content/uploads/sites/2/2023/02/NorskBilsport-Bilcross-RGB-Large.png" alt="Norsk Bilsport Logo">
        <h1 id="admin-title"><?php echo wp_get_document_title(); ?></h1>
    </div>

    <div id="button-container">
        
        <button class="menu-btn" id="oversikt-btn">Oversikt m/Total</button>
        
        <!--
            Denne knappen er deaktivert, men skal kunne aktiveres senere.
            <button class="menu-btn" id="oversikt-bud-btn">Oversikt m/Bud</button>
        -->
        <button id="do-draw-btn" rowspan="2">Utfør Trekning</button>
        <?php
            $current_state = get_option('my_plugin_hide_add_to_cart_buttons', 'no');
            $background_color = $current_state === 'yes' ? 'green' : 'red';
            $button_text = $current_state === 'yes' ? 'Start Bud' : 'Stopp Bud';
            echo '<button id="stop-btn" data-hide="' . $current_state . '" style="background-color: ' . $background_color . ';">' . $button_text . '</button>';
        ?>

        <input type="text" id="search-field" placeholder="Søk på Start/Ordre Nr" />
        <button id="execute-search-btn">Søk</button>
        <div class="radio-container" id="radio-box">
            <input type="radio" id="product-nr" name="search-type" value="product" checked />
            <label id="product-nr-lbl" for="product-nr">StartNr</label>
            <input type="radio" id="order-nr" name="search-type" value="order"/>
            <label id="order-nr-lbl" for="order-nr">OrdreNr</label>
        </div>
    </div>

    <div id="content-area">
    
    <p><b><u>Oversikt m/Total:</u></b><br>
    Dette valget gir deg en oversikt over alle biler som har bud på seg, og en sum total over alle bud per bil. 
    <br>
    Og en sum total over alle bud på alle biler.</p>

    <!--
    <p><b><u>Oversikt m/Bud:</u></b><br>
    Denne oversikten går litt mer i dybden og gir deg en oversikt over alle biler med bud, med ekstra informasjon
    <br> 
    over hvilke ordre som har bud på bilen, men kontakt informasjon og antall bud, og fra hvilken ordre disse budene stammer fra.
    </p>
    -->

    <p><b><u>Utfør Trekning:</u></b><br>
    Denne knappen vil foreta en trekning blant alle ordre i status: "Behandler".
    <br>
    Trekningen vil skje umiddelbart, og som bruker av dette systemet vil du kun få frem
    <br>
    resultatet av trekningnen i form av:<br><br>
    <b>Bil navn</b>
    <br>
    Ordre nr - Kunde navn - Kunde tlf
    </p>

    <i>Du vil kunne lagre både oversiktene og trekningen som en PDF med "Lagre som PDF" knappen som kommer opp øverst til venstre.</i>
    <br>
    
    <p>Trekningen er endelig, og vil ikke kunne gjentaes da alle ordre settes til status "Fullført" ved endt trekning.</p>

    <p><b><u>Søkefelt:</u></b><br>
    Ved hjelp av søkefeltet kan du søke opp enten et spesifikt startnummer,
    <br>
    for å kunne se alle ordre tilknyttet denne bilen.
    <br><br>
    Eller, du kan søke opp et spesifikk ordrenummer,
    <br>
    for å kunne se alle bilene på denne ordren.</p>

    <p><b><u>Stop/Start bud</u></b><br>
    Dette er en (toggle/av og på) knapp. 
    <br>
    Om den er rød med teksten "Stopp Bud": Så er det fult mulig å legge inn nye bud, men du kan stoppe dette ved å trykke på knappen.
    <br>
    Om den er grønn med teksten "Start Bud": Så er det ikke lenger mulig å legge inn bud, men du kan starte det opp igjen ved å trykke på knappen.
    </p>

    </div>
    <?php
}

function magnur_trekning_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] === 'magnur-trekning-admin') {
        wp_enqueue_script('jquery');
        wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.3.3/html2canvas.min.js', array(), '1.3.3', true);
        wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js', array('html2canvas'), '2.3.1', true);
        wp_enqueue_script('jspdf-autotable', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js', array('jspdf'), '3.5.23', true);
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.3.3/dist/sweetalert2.min.js', array(), '11.3.3', true);
        wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.3.3/dist/sweetalert2.min.css', array(), '11.3.3');
        wp_enqueue_script('magnur-trekning-ajax', plugin_dir_url(__FILE__) . 'magnur-trekning-ajax.js', array('jquery', 'jspdf', 'html2canvas', 'jspdf-autotable'), '1.0.0', true);
        $current_state = get_option('my_plugin_hide_add_to_cart_buttons', 'no');
        wp_localize_script('magnur-trekning-ajax', 'magnurTrekning', array('ajaxurl' => admin_url('admin-ajax.php'), 'currentState' => $current_state));
    }
}
add_action('admin_enqueue_scripts', 'magnur_trekning_admin_scripts');

function magnur_trekning_oversikt() {
    global $wpdb;

    //Prepare the SQL query
    $sql = "SELECT p.ID as product_id, p.post_title as product_title, SUM(om_qty.meta_value) as total_orders
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_name = p.post_title
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_qty ON om_qty.order_item_id = oi.order_item_id AND om_qty.meta_key = '_qty'
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_pid ON om_pid.order_item_id = oi.order_item_id AND om_pid.meta_key = '_product_id' AND om_pid.meta_value = p.ID
            JOIN {$wpdb->prefix}posts o ON o.ID = oi.order_id
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND o.post_status = 'wc-processing'
            GROUP BY p.ID, p.post_title
            ORDER BY p.ID";

    // Prepare the statement
    $stmt = $wpdb->prepare($sql);

    // Execute the statement
    $results = $wpdb->get_results($stmt, ARRAY_A);

    $totalProducts = 0;
    $totalOrders = 0;
    $totalQuantity = 0;

        // Process and display the results
        if (count($results) > 0) {
            
            echo "<h1><u>Oversikt m/Total antall bud:</u></h1>";

            echo "<table id='oversikt-table'>";
            echo "
                <tr>
                    <th><b> ID </b></th>
                    <th><b> Bil tittel </b></th>
                    <th><b> Bud Total </b></th>
                </tr>";

            $SumTotalOrders = 0;

            foreach ($results as $result) {
                echo "
                <tr>
                    <td>#{$result['product_id']}</td>
                    <td width='550px'>{$result['product_title']}</td>
                    <td class='center'>{$result['total_orders']}</td>
                </tr>";
                $SumTotalOrders += $result['total_orders'];
            }
            echo "<tr>";
            echo "<td>Sum Total:</td>";
            echo "<td></td>";
            echo "<td class='center'>{$SumTotalOrders}</td>";
            echo "</table>";
        } else {
            echo "<p>Ingen ordre funnet.</p>";
        }
    
        wp_die(); // This is required to terminate the AJAX request properly
}
    add_action('wp_ajax_magnur_trekning_oversikt', 'magnur_trekning_oversikt');

    /* Denne funksjonen er deaktivert, men skal kunne brukes senere.

    function magnur_trekning_oversikt_bud() {
        global $wpdb;
    
        $sql = "SELECT p.ID as product_id, p.post_title as product_title, o.ID as order_id, o.post_status as order_status,
                om_fn.meta_value as first_name, om_ln.meta_value as last_name, om_phone.meta_value as phone, om_qty.meta_value as qty
                FROM {$wpdb->prefix}posts p
                JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_name = p.post_title
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_qty ON om_qty.order_item_id = oi.order_item_id AND om_qty.meta_key = '_qty'
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_pid ON om_pid.order_item_id = oi.order_item_id AND om_pid.meta_key = '_product_id' AND om_pid.meta_value = p.ID
                JOIN {$wpdb->prefix}posts o ON o.ID = oi.order_id
                JOIN {$wpdb->prefix}postmeta om_fn ON om_fn.post_id = o.ID AND om_fn.meta_key = '_billing_first_name'
                JOIN {$wpdb->prefix}postmeta om_ln ON om_ln.post_id = o.ID AND om_ln.meta_key = '_billing_last_name'
                JOIN {$wpdb->prefix}postmeta om_phone ON om_phone.post_id = o.ID AND om_phone.meta_key = '_billing_phone'
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                AND o.post_status = 'wc-processing'
                ORDER BY p.ID, o.ID";
    
        $stmt = $wpdb->prepare($sql);
        $results = $wpdb->get_results($stmt, ARRAY_A);
    
        if (count($results) > 0) {
            $current_product_id = -1;
            $first_table = true;

            echo "<h1><u>Oversikt m/Bud Info:</u></h1>";
        
            foreach ($results as $result) {
                if ($current_product_id !== $result['product_id']) {
                    if ($current_product_id !== -1) {
                        echo "</table>";
                    }
                    $current_product_id = $result['product_id'];
                    echo "<h2>#{$result['product_id']} - {$result['product_title']}</h2>";
        
                    if ($first_table) {
                        echo '<table class="bud-table" data-product="' . htmlspecialchars(json_encode(array('id' => $result['product_id'], 'title' => $result['product_title'])), ENT_QUOTES, 'UTF-8') . '">';
                        $first_table = false;
                    } else {
                        echo '<table data-product="' . htmlspecialchars(json_encode(array('id' => $result['product_id'], 'title' => $result['product_title'])), ENT_QUOTES, 'UTF-8') . '">';
                    }
        
                    echo "
                        <tr>
                            <th width='60px'><b>Order Nr.</b></th>
                            <th width='200px'><b>Navn</b></th>
                            <th width='80px'><b>Tlf</b></th>
                            <th width='60px'><b>Ant. Bud</b></th>
                        </tr>";
                }
        
                echo "
                <tr>
                    <td align='center'>{$result['order_id']}</td>
                    <td align='center'>{$result['first_name']} {$result['last_name']}</td>
                    <td align='center'>{$result['phone']}</td>
                    <td align='center'>{$result['qty']}</td>
                </tr>";
            }
        
            echo "</table>";
        } else {
            echo "No products found.";
        }
        
        
        wp_die();
        }
        add_action('wp_ajax_magnur_trekning_oversikt_bud', 'magnur_trekning_oversikt_bud');
    */

    function magnur_trekning_admin_styles() {
        if (isset($_GET['page']) && $_GET['page'] === 'magnur-trekning-admin') {
            wp_enqueue_style('magnur-trekning-styles', plugin_dir_url(__FILE__) . 'magnur-trekning-styles.css', array(), '1.0.0');
        }
    }
    add_action('admin_enqueue_scripts', 'magnur_trekning_admin_styles');

    function search_single_product() {
        if (!isset($_POST['product_number']) || !preg_match('/^\d{1,4}$/', $_POST['product_number'])) {
            echo 'no_results';
            wp_die();
        }
    
        $product_number = $_POST['product_number'];
    
        global $wpdb;
    
        $sql = $wpdb->prepare("SELECT p.ID as product_id, p.post_title as product_title, o.ID as order_id, o.post_status as order_status,
            om_fn.meta_value as first_name, om_ln.meta_value as last_name, om_phone.meta_value as phone, om_qty.meta_value as qty
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_name = p.post_title
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_qty ON om_qty.order_item_id = oi.order_item_id AND om_qty.meta_key = '_qty'
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_pid ON om_pid.order_item_id = oi.order_item_id AND om_pid.meta_key = '_product_id' AND om_pid.meta_value = p.ID
            JOIN {$wpdb->prefix}posts o ON o.ID = oi.order_id
            JOIN {$wpdb->prefix}postmeta om_fn ON om_fn.post_id = o.ID AND om_fn.meta_key = '_billing_first_name'
            JOIN {$wpdb->prefix}postmeta om_ln ON om_ln.post_id = o.ID AND om_ln.meta_key = '_billing_last_name'
            JOIN {$wpdb->prefix}postmeta om_phone ON om_phone.post_id = o.ID AND om_phone.meta_key = '_billing_phone'
            WHERE p.post_type = 'product' AND p.post_status = 'publish' AND p.post_title REGEXP CONCAT('(^|/)', %s, '( |/)')
            AND o.post_status = 'wc-processing'
            ORDER BY p.ID, o.ID", $product_number);
    
        $results = $wpdb->get_results($sql, ARRAY_A);
    
        if (count($results) > 0) {
            // Prepare the JSON response
            $response = [
                'productId' => $results[0]['product_id'],
                'productTitle' => $results[0]['product_title'],
                'orders' => []
            ];
    
            // Add order details
            foreach ($results as $result) {
                $order = [
                    'orderNr' => $result['order_id'],
                    'customerName' => $result['first_name'] . ' ' . $result['last_name'],
                    'phone' => $result['phone'],
                    'quantity' => $result['qty']
                ];
                $response['orders'][] = $order;
            }
    
            // Send the JSON response
            echo json_encode($response);
        } else {
            echo 'no_results';
        }
    
        wp_die();
    }
    add_action('wp_ajax_search_single_product', 'search_single_product');

    function search_single_order() {

        if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
            echo 'no_results';
            wp_die();
        }
    
        $order_id = intval($_POST['order_id']);

        global $wpdb;
    
        $sql = $wpdb->prepare("SELECT o.ID as order_id, o.post_status as order_status,
                    om_fn.meta_value as first_name, om_ln.meta_value as last_name, om_phone.meta_value as phone,
                    p.ID as product_id, p.post_title as product_title, om_qty.meta_value as qty
                    FROM {$wpdb->prefix}posts o
                    JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = o.ID
                    JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_qty ON om_qty.order_item_id = oi.order_item_id AND om_qty.meta_key = '_qty'
                    JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_pid ON om_pid.order_item_id = oi.order_item_id AND om_pid.meta_key = '_product_id'
                    JOIN {$wpdb->prefix}posts p ON p.ID = om_pid.meta_value AND p.post_type = 'product' AND p.post_status = 'publish'
                    JOIN {$wpdb->prefix}postmeta om_fn ON om_fn.post_id = o.ID AND om_fn.meta_key = '_billing_first_name'
                    JOIN {$wpdb->prefix}postmeta om_ln ON om_ln.post_id = o.ID AND om_ln.meta_key = '_billing_last_name'
                    JOIN {$wpdb->prefix}postmeta om_phone ON om_phone.post_id = o.ID AND om_phone.meta_key = '_billing_phone'
                    WHERE o.post_type = 'shop_order' AND o.post_status = 'wc-processing' AND o.ID = %d
                    ORDER BY o.ID, p.ID", $order_id);
    
        $results = $wpdb->get_results($sql, ARRAY_A);

        if (count($results) > 0) {
            $response = [
                'orderId' => $results[0]['order_id'],
                'customerName' => $results[0]['first_name'] . ' ' . $results[0]['last_name'],
                'phone' => $results[0]['phone'],
                'products' => []
            ];

            foreach ($results as $result) {
                $product = [
                    'productId' => $result['product_id'],
                    'productTitle' => $result['product_title'],
                    'quantity' => $result['qty']
                ];
                $response['products'][] = $product;
            }

            echo json_encode($response);
        } else {
            echo 'no_results';
        }

        wp_die();
    }
    add_action('wp_ajax_search_single_order', 'search_single_order');

    function do_random_draw() {
        global $wpdb;
    
        $sql = "SELECT p.ID as product_id, p.post_title as product_title, o.ID as order_id, om_qty.meta_value as quantity,
        om_fname.meta_value as billing_first_name, om_lname.meta_value as billing_last_name, om_phone.meta_value as billing_phone
        FROM {$wpdb->prefix}posts p
        JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_name = p.post_title
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_qty ON om_qty.order_item_id = oi.order_item_id AND om_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta om_pid ON om_pid.order_item_id = oi.order_item_id AND om_pid.meta_key = '_product_id' AND om_pid.meta_value = p.ID
        JOIN {$wpdb->prefix}posts o ON o.ID = oi.order_id
        JOIN {$wpdb->prefix}postmeta om_fname ON om_fname.post_id = o.ID AND om_fname.meta_key = '_billing_first_name'
        JOIN {$wpdb->prefix}postmeta om_lname ON om_lname.post_id = o.ID AND om_lname.meta_key = '_billing_last_name'
        JOIN {$wpdb->prefix}postmeta om_phone ON om_phone.post_id = o.ID AND om_phone.meta_key = '_billing_phone'
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
        AND o.post_status = 'wc-processing'
        ORDER BY p.ID, o.ID";
    
        $results = $wpdb->get_results($sql, ARRAY_A);
        $orders = [];
    
        foreach ($results as $result) {
            if (!isset($orders[$result['product_id']])) {
                $orders[$result['product_id']] = [
                    'product_title' => $result['product_title'],
                    'orders' => []
                ];
            }
    
            $orders[$result['product_id']]['orders'][] = [
                'order_id' => $result['order_id'],
                'quantity' => $result['quantity'],
                'billing_first_name' => $result['billing_first_name'],
                'billing_last_name' => $result['billing_last_name'],
                'billing_phone' => $result['billing_phone']
            ];
        }
    
        $winners = [];
    
        foreach ($orders as $product_id => $product_data) {
            $winner = select_winner($product_data['orders']);
            $winners[] = [
                'productId' => $product_id,
                'productTitle' => $product_data['product_title'],
                'winner' => [
                    'orderId' => $winner['order_id'],
                    'customerName' => $winner['billing_first_name'] . ' ' . $winner['billing_last_name'],
                    'phone' => $winner['billing_phone']
                ]
            ];
        }

        // Update the post_status of the orders that were part of the draw to 'wc-completed'
        $order_ids = array_unique(array_column($results, 'order_id'));
        if (!empty($order_ids)) {
            $order_ids_placeholder = implode(', ', array_fill(0, count($order_ids), '%d'));
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}posts
                    SET post_status = 'wc-completed'
                    WHERE ID IN ($order_ids_placeholder) AND post_status = 'wc-processing'",
                    $order_ids
                )
            );
        }

    
        echo json_encode($winners);
    
        wp_die();
    }    
    
    add_action('wp_ajax_do_random_draw', 'do_random_draw');
    
    function select_winner($orders) {
        $total_tickets = array_sum(array_column($orders, 'quantity'));
        $rand_ticket = rand(1, $total_tickets);
        $ticket_count = 0;
    
        foreach ($orders as $order) {
            $ticket_count += $order['quantity'];
            if ($rand_ticket <= $ticket_count) {
                return $order;
            }
        }
    
        return null;

        /*
        Trekningsprosessen som er implementert i select_winner() funksjonen bruker 
        en vektet tilfeldig utvalg, noe som betyr at hver ordres sannsynlighet 
        for å vinne er proporsjonal med mengden. Her er en trinnvis forklaring 
        av prosessen:

        Beregn det totale antallet billetter: Funksjonen beregner først det totale 
        antallet billetter (mengder) for alle ordrer kombinert. Dette gjøres ved å 
        bruke array_sum(array_column($orders, 'quantity')), som legger sammen alle 
        mengdene i ordre-arrayet.

        Generer et tilfeldig billettnummer: Funksjonen genererer et tilfeldig 
        billettnummer mellom 1 og det totale antallet billetter ved å bruke 
        rand(1, $total_tickets). Dette tilfeldige tallet vil bli brukt til å 
        bestemme den vinnende ordren.

        Iterer gjennom ordrer: Funksjonen itererer gjennom ordrer, og for hver 
        ordre legger den til mengden i en ticket_count-variabel. Denne variabelen 
        holder styr på den kumulative summen av mengder når vi itererer gjennom 
        ordrer.

        Bestem vinneren: Hvis det tilfeldige billettnummeret som ble generert i 
        trinn 2 er mindre enn eller lik den gjeldende ticket_count, er den gjeldende 
        ordren vinneren. Dette skjer fordi den nåværende ordrens "billettområde" 
        dekker den tilfeldig valgte billetten.

        La oss bruke eksemplet du ga tidligere med 3 ordrer som har mengder på 50, 
        40 og 10, for totalt 100 billetter. I dette tilfellet vil det tilfeldige 
        billettnummeret som genereres i trinn 2 være mellom 1 og 100.

        Hvis det tilfeldige billettnummeret er mellom 1 og 50, vil den første ordren vinne fordi billetten faller innenfor billettområdet (1 til 50).
        Hvis det tilfeldige billettnummeret er mellom 51 og 90, vil den andre ordren vinne fordi billetten faller innenfor billettområdet (51 til 90).
        Hvis det tilfeldige billettnummeret er mellom 91 og 100, vil den tredje ordren vinne fordi billetten faller innenfor billettområdet (91 til 100).

        Sannsynligheten for at hver ordre vinner er proporsjonal med mengden (dvs. størrelsen på billettområdet). 
        I dette eksemplet har den første ordren en 50 % sjanse for å vinne, den andre ordren har en 40 % sjanse, og den tredje ordren har en 10 % sjanse.
        */
    }

    function my_plugin_set_hide_add_to_cart_buttons_option() {
        if (!isset($_POST['state'])) {
            echo "Invalid state.";
            wp_die();
        }
    
        // Set the option to the received state
        update_option('my_plugin_hide_add_to_cart_buttons', sanitize_text_field($_POST['state']));
    
        echo "Success";
        wp_die(); // This is required to terminate immediately and return a proper response
    }
    add_action('wp_ajax_my_plugin_set_hide_add_to_cart_buttons_option', 'my_plugin_set_hide_add_to_cart_buttons_option');
    

    function my_plugin_hide_add_to_cart_buttons() {
        // Get the option value
        $hide_buttons = get_option('my_plugin_hide_add_to_cart_buttons', 'no');
    
        if ( $hide_buttons === 'yes' && ! is_user_logged_in() ) {
            // Remove 'Add to Cart' buttons on shop page (archive)
            remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
    
            // Remove 'Add to Cart' button on single product page
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        }
    }
    add_action( 'wp', 'my_plugin_hide_add_to_cart_buttons' );
    
    

?>