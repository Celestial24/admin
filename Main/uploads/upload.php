<?php
// ADMIN/Main/uploads/transfer_file.php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "documan";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$documents = [];
$transfers = [];

// Fetch documents for dropdown (only documents that exist)
$docResult = $conn->query("SELECT id, name, department FROM documents ORDER BY name ASC");
if ($docResult) {
    while ($doc = $docResult->fetch_assoc()) {
        $documents[] = $doc;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_id     = intval($_POST['document_id'] ?? 0);
    $to_department   = trim($_POST['to_department'] ?? '');
    $transferred_by  = trim($_POST['transferred_by'] ?? '');

    // Validate required fields
    if ($document_id <= 0 || empty($to_department) || empty($transferred_by)) {
        $message = "❌ Please fill in all required fields.";
    } else {
        // Get current department of document for "from_department"
        $stmt = $conn->prepare("SELECT department FROM documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $stmt->bind_result($from_department);
        if ($stmt->fetch()) {
            $stmt->close();

            // Insert transfer record
            $stmt = $conn->prepare("INSERT INTO transfer_files (document_id, from_department, to_department, transferred_by, transferred_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $document_id, $from_department, $to_department, $transferred_by);
            
            if ($stmt->execute()) {
                // Update document's department to new one
                $updateStmt = $conn->prepare("UPDATE documents SET department = ? WHERE id = ?");
                $updateStmt->bind_param("si", $to_department, $document_id);
                $updateStmt->execute();
                $updateStmt->close();

                $message = "✅ Document transferred successfully.";
            } else {
                $message = "❌ Failed to save transfer: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "❌ Document not found.";
            $stmt->close();
        }
    }
}

// Fetch all transfers with document name for display
$transfersResult = $conn->query(
    "SELECT tf.id, tf.from_department, tf.to_department, tf.transferred_by, tf.transferred_at, d.name AS document_name
     FROM transfer_files tf
     JOIN documents d ON tf.document_id = d.id
     ORDER BY tf.transferred_at DESC"
);

if ($transfersResult) {
    while ($row = $transfersResult->fetch_assoc()) {
        $transfers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transfer Document</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 p-6">

  <?php if ($message): ?>
    <div class="mb-4 p-3 rounded <?= strpos($message, '✅') === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <form action="transfer_file.php" method="POST" class="mb-6 bg-white p-6 rounded shadow max-w-md mx-auto space-y-4">
    <h1 class="text-xl font-semibold mb-4">Transfer Document</h1>

    <label for="document_id" class="block font-medium mb-1">Select Document:</label>
    <select name="document_id" id="document_id" required class="border px-4 py-2 w-full rounded">
      <option value="">-- Select Document --</option>
      <?php foreach ($documents as $doc): ?>
        <option value="<?= $doc['id'] ?>">
          <?= htmlspecialchars($doc['name']) ?> (Current: <?= htmlspecialchars($doc['department']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label for="to_department" class="block font-medium mb-1">Transfer To Department:</label>
    <select name="to_department" id="to_department" required class="border px-4 py-2 w-full rounded">
      <option value="">-- Select Department --</option>
      <option value="Front Desk">Front Desk</option>
      <option value="Kitchen">Kitchen</option>
      <option value="HR">HR</option>
      <option value="Housekeeping">Housekeeping</option>
    </select>

    <label for="transferred_by" class="block font-medium mb-1">Transferred By:</label>
    <input type="text" id="transferred_by" name="transferred_by" required placeholder="Your name" class="border px-4 py-2 w-full rounded" />

    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Transfer</button>
  </form>

  <section class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-lg font-semibold mb-4">📄 Transfer History</h2>
    <?php if (count($transfers) === 0): ?>
      <p class="text-center text-gray-500">No transfer records found.</p>
    <?php else: ?>
      <table class="min-w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 text-left">Document Name</th>
            <th class="px-4 py-2 text-left">From Department</th>
            <th class="px-4 py-2 text-left">To Department</th>
            <th class="px-4 py-2 text-left">Transferred By</th>
            <th class="px-4 py-2 text-left">Transferred At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transfers as $transfer): ?>
            <tr class="border-t">
              <td class="px-4 py-2"><?= htmlspecialchars($transfer['document_name']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($transfer['from_department']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($transfer['to_department']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($transfer['transferred_by']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($transfer['transferred_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

</body>
</html>
