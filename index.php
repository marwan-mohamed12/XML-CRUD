<?php
// Initialize the XML file path
$xmlFilePath = 'data.xml';
$xml = simplexml_load_file($xmlFilePath);
if (!file_exists($xmlFilePath)) {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $root = $dom->createElement('contacts');
    $dom->appendChild($root);
    $dom->save($xmlFilePath);
} else {
    $dom = new DOMDocument();
    $dom->load($xmlFilePath);
}

function insertContact($name, $phone, $address, $email)
{
    global $dom, $xmlFilePath, $xml;
    $contact = $dom->createElement('contact');
    $id = $xml->count() + 1;
    $contact->appendChild($dom->createElement('id', $id));
    $contact->appendChild($dom->createElement('name', $name));
    $contact->appendChild($dom->createElement('phone', $phone));
    $contact->appendChild($dom->createElement('address', $address));
    $contact->appendChild($dom->createElement('email', $email));
    $dom->documentElement->appendChild($contact);
    $dom->save($xmlFilePath);
    return ['id' => $id, 'name' => $name, 'phone' => $phone, 'address' => $address, 'email' => $email];
}

function updateContact($id, $newName, $newPhone, $newAddress, $newEmail)
{
    global $dom, $xmlFilePath;
    $contacts = $dom->getElementsByTagName('contact');
    foreach ($contacts as $contact) {
        echo $contact->getElementsByTagName('id')->item(0)->nodeValue;
        if ((int)$contact->getElementsByTagName('id')->item(0)->nodeValue == (int)$id) {
            echo "id: " . $contact->getElementsByTagName('id')->item(0)->nodeValue . ", target Id: " . $id;
            $contact->getElementsByTagName('name')->item(0)->nodeValue = $newName;
            $contact->getElementsByTagName('phone')->item(0)->nodeValue = $newPhone;
            $contact->getElementsByTagName('address')->item(0)->nodeValue = $newAddress;
            $contact->getElementsByTagName('email')->item(0)->nodeValue = $newEmail;
            $dom->save($xmlFilePath);
            return ['id' => $id, 'name' => $newName, 'phone' => $newPhone, 'address' => $newAddress, 'email' => $newEmail];
        }
    }
}

function deleteContact($id)
{
    global $dom, $xmlFilePath;
    $contacts = $dom->getElementsByTagName('contact');
    foreach ($contacts as $contact) {
        if ((int)$contact->getElementsByTagName('id')->item(0)->nodeValue == (int)$id) {
            $dom->documentElement->removeChild($contact);
            $dom->save($xmlFilePath);
            break;
        }
    }

    $num = 0;
    foreach ($contacts as $contact) {
        $contact->getElementsByTagName('id')->item(0)->nodeValue = ++$num;
    }
    $dom->save($xmlFilePath);
}

function searchContacts($searchValue, $searchField)
{
    global $dom;
    $searchResults = [];
    $contacts = $dom->getElementsByTagName('contact');
    foreach ($contacts as $contact) {
        $currentFieldValue = $contact->getElementsByTagName($searchField)->item(0)->nodeValue;
        if (str_contains(strtolower($currentFieldValue), strtolower($searchValue))) {
            array_push($searchResults, $contact);
        }
    }

    foreach ($searchResults as $contact) {
        $id = (int)htmlspecialchars($contact->getElementsByTagName('id')->item(0)->nodeValue);
        $name = htmlspecialchars($contact->getElementsByTagName('name')->item(0)->nodeValue);
        $email = htmlspecialchars($contact->getElementsByTagName('email')->item(0)->nodeValue);
        $phone = htmlspecialchars($contact->getElementsByTagName('phone')->item(0)->nodeValue);
        $address = htmlspecialchars($contact->getElementsByTagName('address')->item(0)->nodeValue);
    }

    return ['id' => $id, 'name' => $name, 'phone' => $phone, 'address' => $address, 'email' => $email];
}

$result = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)$_POST['id'] ?? -1;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $searchValue = trim($_POST['searchValue'] ?? '');
    $searchField = $_POST['searchField'] ?? 'name';

    switch ($action) {
        case 'Insert':
            $result = insertContact($name, $phone, $address, $email);
            break;
        case 'Update':
            $result = updateContact($id, $name, $phone, $address, $email);
            break;
        case 'Delete':
            deleteContact($id);
            $result = searchContacts($id - 1, 'id');
            break;
        case 'Search':
            $result = searchContacts($searchValue, $searchField);
            break;
        case 'prev':
            $id -= 1;
            $result = searchContacts($id < 1 ? $xml->count() : $id, 'id');
            break;
        case 'next':
            $id += 1;
            $result = searchContacts($id > $xml->count() ? 1 : $id, 'id');
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body class="container mt-5 p-3">
    <form action="index.php" method="POST" class="d-flex flex-column gap-3">
        <div class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search Value" name="searchValue" aria-label="Search Value">
                <select class="form-select" name="searchField">
                    <!-- <option value="id">id</option> -->
                    <option value="name">Name</option>
                    <option value="phone">Phone</option>
                    <option value="address">Address</option>
                    <option value="email">Email</option>
                </select>
                <button class="btn btn-outline-secondary" type="submit" name="action" value="Search">Search</button>
            </div>
        </div>
        <div class="form-group">
            <label for="id" class="d-none">id:</label>
            <input type="text" class="form-control d-none" id="id" name="id" value="<?php echo $result['id'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo $result['name'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone:</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $result['phone'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo $result['address'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label for="email">Email address:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo $result['email'] ?? '' ?>">
        </div>
        <div class="d-flex flex-row justify-content-evenly">
            <button type="submit" name="action" value="prev" class="btn btn-secondary">prev</button>
            <button type="submit" name="action" value="Insert" class="btn btn-primary">Insert</button>
            <button type="submit" name="action" value="Update" class="btn btn-info">Update</button>
            <button type="submit" name="action" value="Delete" class="btn btn-danger">Delete</button>
            <button type="submit" name="action" value="next" class="btn btn-secondary">Next</button>
        </div>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>