<?PHP
#error_reporting(E_ERROR | E_PARSE);

# Error Function
function Wcapi_error($id,$sku) {
    $error_log = '/home/pi/phpscripts/cml-error-log.txt';
    $datetime = Date("l") . " " . date("Y-m-d H:i:s");
    $current1 = file_get_contents($error_log);
    $current1 .= "Error on product : " . $id . " : " . $sku . " : " . $datetime . "\n";
    file_put_contents($error_log, $current1);
    echo "\nI've shit myself\n";
}

$outofstock = 0;
$lowstock = 0;
$instock = 0;
$errors = 0;

# Logging
$datetime = Date("l") . " " . date("Y-m-d H:i:s");
$file = '/home/pi/phpscripts/cml-log.txt';
$current = file_get_contents($file);
$current .= "\n -------------- \nStarting\n" . $datetime;
file_put_contents($file, $current);

# Send End Email
$datetime = Date("l") . " " . date("F j, Y, g:i a");
$to = 'richard@hamme.co.uk';
$subject = 'CML Update ' . $datetime;
$headers = 'From: semcs-scripts@outlook.com' . "\r\n" . 'Reply-To: semcs-scripts@outlook.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$message = "\nCML Update Started " . $datetime;
mail($to, $subject, $message, $headers);

$quiet = 0;
print "Processing .... <br/>\n";

# Get the stock file
#
$file_contents = file_get_contents("https://www.cmltradedirect.co.uk/csvstocklevel.php");
$file = fopen("/home/pi/phpscripts/cml_stocklevels.csv", "w");
fwrite($file, $file_contents);
fclose($file);

# Set up the Woocommerce API connector
require __DIR__ . "/vendor/autoload.php";
use Automattic\WooCommerce\Client;
$wcapi = new Client("https://semcs.co.uk", "ck_865b3bc868ab52ddd5da6732024fd981389c9fad", "cs_432e1ecadee32df2ded46ab4735c6af255dc8644", ["version" => "wc/v2", 'timeout' => "400"]);

#print_r($wcapi);
if ($quiet == 0) print_r("<br/>\n");

# Connect to the SEMCS Database
$mysqli = new mysqli('nonsence.co.uk', 'nonsense', 'morenonsense', 'gibbergibber') or die('Failed to connect');
if ($mysqli->connect_errno)
{
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

# Open the CML stock file
$cml_stock = fopen("/home/pi/phpscripts/cml_stocklevels.csv", "r");

# Loop through the stock file
$i = 0;
while (($line_of_text = fgetcsv($cml_stock)) !== false)
{
    if ($i == 0)
    {
        $line_of_text = fgetcsv($cml_stock, 1024);
    }
    $i++;

    # Get the row from 759_posts
    if ($result = $mysqli->query("SELECT * from 759_posts WHERE post_excerpt = '" . $line_of_text[0] . "';"))
    {
        # Get the ID
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $id = $row['ID'];
        
        # Get the stock information from 759_wc_product_meta_lookup
        $result = $mysqli->query("SELECT * FROM 759_wc_product_meta_lookup where product_id = '" . $id . "';");
        $row = $result->fetch_array(MYSQLI_ASSOC);

        # No Match
        if ($result->num_rows == 0)
        {
            if ($quiet == 0) print_r($line_of_text[0] . " : No Match");
        }
        # Found a match
        else
        {
            if ($quiet == 0) print_r("* " . $row['sku'] . " : " . $row['stock_status'] . " : " . $row['stock_quantity'] . " : " . $row['product_id']);
            $sku = $row['sku'];
            # Process the match
            if ($row['stock_quantity'] > 0)
            {
                if ($quiet == 0) print_r(" - You have stock *");
            }
            else
            {
                if ($line_of_text[1] == "Out of stock")
                {
                    if ($quiet == 0) print_r(" - Out of stock at CML *");
                    $outofstock++;

                    $data = ["backordered" => "False", "stock_status" => "outofstock", "backorders" => "no", ];
                    #Update    
                    try
                    {
                        $wcapi->put("products/" . $id, $data);
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }

                    $data = ['id' > $id, 'meta_data' => array(
                        ['key' => 'available_on_backorder',
                        'value' => '']
                    ) ];
                    #Update    
                    try
                    {
                        $wcapi->put("products/" . $id, $data);                        
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }
                }

                if ($line_of_text[1] == "Low stock")
                {
                    if ($quiet == 0) print_r(" - Low Stock at CML *");
                    $lowstock++;

                    $data = ["backordered" => "False", "stock_status" => "outofstock", "backorders" => "no", ];
                    #Update                        
                    try
                    {
                        $wcapi->put("products/" . $id, $data);
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }

                    $data = ['id' > $id, 'meta_data' => array(
                        ['key' => 'available_on_backorder',
                        'value' => '']
                    ) ];
                    #Update    
                    try
                    {
                        $wcapi->put("products/" . $id, $data);                        
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }

                }

                if ($line_of_text[1] == "In stock")
                {
                    if ($quiet == 0) print_r(" - In stock at CML *");
                    $instock++;

                    $data = ["backordered" => "False", "stock_status" => "outofstock", "backorders" => "notify", ];
                    #Update    
                    try
                    {
                        $wcapi->put("products/" . $id, $data);
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }                    

                    $data = ['id' > $id, 'meta_data' => array(
                        ['key' => 'available_on_backorder',
                        'value' => 'Suppliers Stock']
                    ) ];
                    #Update    
                    try
                    {
                        $wcapi->put("products/" . $id, $data);                        
                    }
                    catch(Throwable $t)
                    {
                        Wcapi_error($id,$sku);
                        $errors++;
                    }
                }

            }
        }
        if ($quiet == 0) print_r("<br/>\n");
    }
}

$oos = "\n\nOut of Stock Items : " . $outofstock . "\n";
$low = "Low Stock Items  : " . $lowstock . "\n";
$ins = "In Stock Items : " . $instock . "\n";
$errors = "Errors : " . $errors . "\n";

$error_log = '/home/pi/phpscripts/cml-error-log.txt';
$current1 = file_get_contents($error_log);
$current1 = "\n\n" . $current1 . "\n\n";

print "Finished!";

# Send End Email
$datetime = Date("l") . " " . date("F j, Y, g:i a");
$to = 'richard@hamme.co.uk, sloughelectricmodelcars@gmail.com';
$subject = 'CML Update Update ' . $datetime;
$headers = 'From: semcs-scripts@outlook.com' . "\r\n" . 'Reply-To: semcs-scripts@outlook.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$message = "\nCML Update Finished " . $datetime . $oos . $low . $ins . $errors . $current1;
mail($to, $subject, $message, $headers);

# Logging
$datetime = Date("l") . " " . date("Y-m-d H:i:s");
$file = '/home/pi/phpscripts/cml-log.txt';
$current = file_get_contents($file);
$current .= "\nFinished\n" . $datetime;
file_put_contents($file, $current);

?>
