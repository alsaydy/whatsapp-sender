
<?php
$instanceId = "instance112832";
$token = "1j6hcc8n7svgqlv1";

function uploadToUltraMsg($filePath, $token, $instanceId) {
    $mimeType = mime_content_type($filePath);
    $filename = basename($filePath);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ultramsg.com/$instanceId/media/upload?token=$token");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        "file" => new CURLFile($filePath, $mimeType, $filename)
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ["error" => curl_error($ch)];
    }
    curl_close($ch);

    return json_decode($response, true);
}

$numbers = explode("\n", trim($_POST['numbers']));
$message = trim($_POST['message']);
$imagePath = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tempPath = $_FILES['image']['tmp_name'];
    $uploadResult = uploadToUltraMsg($tempPath, $token, $instanceId);

    if (isset($uploadResult['success'])) {
        $imageUrl = $uploadResult['success'];
    } else {
        echo json_encode(["message" => "فشل رفع الصورة إلى UltraMsg. الاستجابة: " . json_encode($uploadResult)]);
        exit;
    }
}

$results = [];

foreach ($numbers as $number) {
    $number = trim($number);
    if ($number === '') continue;

    if (isset($imageUrl)) {
        $params = [
            'token' => $token,
            'to' => $number,
            'image' => $imageUrl,
            'caption' => $message
        ];
        $url = "https://api.ultramsg.com/$instanceId/messages/image";
    } else {
        $params = [
            'token' => $token,
            'to' => $number,
            'body' => $message
        ];
        $url = "https://api.ultramsg.com/$instanceId/messages/chat";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
    $response = curl_exec($ch);
    curl_close($ch);

    $results[] = json_decode($response, true);
}

echo json_encode(["message" => "تم إرسال الرسائل", "responses" => $results]);
?>
