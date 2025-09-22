<?php
session_start();

// Normalize session like user dashboard
$hasLegacySession = isset($_SESSION['user_id']);
$hasStructuredSession = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
if (!$hasStructuredSession && $hasLegacySession) {
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
    $hasStructuredSession = true;
}
if (!$hasStructuredSession) {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'visitor';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkin') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $special_notes = trim($_POST['special_notes'] ?? '');
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    if (empty($full_name) || empty($email) || !$agreement) {
        $error_message = "Please fill all required fields and agree to terms.";
    } else {
        $sql = "INSERT INTO guest_submissions (full_name, email, phone, notes, submitted_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $full_name, $email, $phone, $special_notes);
        
        if ($stmt->execute()) {
            $success_message = "Thank you for checking in! Welcome to our Hotel & Restaurant.";
        } else {
            $error_message = "Error occurred while checking in. Please try again.";
        }
        $stmt->close();
    }
}

// Handle checkout action
if (isset($_GET['action']) && $_GET['action'] == 'checkout' && isset($_GET['id'])) {
    $visitorId = (int)$_GET['id'];
    
    $stmt = $conn->prepare("UPDATE guest_submissions SET checked_out = 1, checked_out_at = NOW() WHERE id = ? AND checked_out = 0");
    $stmt->bind_param("i", $visitorId);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch visitor statistics for monitoring
$stats = [];
$stats['total_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions")->fetch_assoc()['count'];
$stats['active_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE checked_out = 0")->fetch_assoc()['count'];
$stats['checked_out_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(checked_out_at) = CURDATE()")->fetch_assoc()['count'];
$stats['checked_in_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(submitted_at) = CURDATE()")->fetch_assoc()['count'];

// Fetch visitor logs with filtering
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereClause = "1=1";
if ($filter === 'active') {
    $whereClause = "checked_out = 0";
} elseif ($filter === 'checked_out') {
    $whereClause = "checked_out = 1";
} elseif ($filter === 'today') {
    $whereClause = "DATE(submitted_at) = CURDATE()";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereClause .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

$visitorLogsResult = $conn->query("SELECT * FROM guest_submissions WHERE $whereClause ORDER BY submitted_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <title>Visitor Management & Monitoring</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html, body {
      height: 100%;     
      margin: 0;         
      padding: 0;         
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="shadow-lg h-screen fixed top-0 left-0">
    <?php include '../Components/sidebar/sidebar_user.php'; ?>
  </div>

  <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Visitor Management & Monitoring</h2>
        <p class="text-sm text-gray-500 mt-1">Check-in visitors and monitor visitor logs</p>
      </div>
      <div class="flex items-center gap-4">
        <button id="refreshDataBtn" class="bg-green-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 flex items-center gap-2">
          🔄 Refresh Data
        </button>
        <?php include __DIR__ . '/../profile.php'; ?>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-8">
      <!-- Success/Error Messages -->
      <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <!-- Visitor Monitoring Dashboard -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Total Visitors</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['total_visitors'] ?></p>
            </div>
            <div class="text-blue-500 text-3xl">👥</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Active Visitors</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['active_visitors'] ?></p>
            </div>
            <div class="text-green-500 text-3xl">✅</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Checked In Today</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['checked_in_today'] ?></p>
            </div>
            <div class="text-yellow-500 text-3xl">📥</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Checked Out Today</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['checked_out_today'] ?></p>
            </div>
            <div class="text-red-500 text-3xl">📤</div>
          </div>
        </div>
      </div>

      <!-- Terms Section -->
      <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-xl font-bold mb-2">Terms & Conditions</h2>
        <p class="mb-4 font-semibold">Welcome to our Hotel & Restaurant!</p>
        <p class="mb-4">By checking in or dining with us, you agree to the following policies:</p>
        <ul class="list-disc list-inside space-y-2 text-sm text-gray-700">
          <li><strong>Check-In & Check-Out:</strong> Valid ID required. Standard check-in 2:00 PM, check-out 12:00 PM.</li>
          <li><strong>Room & Facility Use:</strong> Guests are responsible for property. Damages will be charged.</li>
          <li><strong>Restaurant Policies:</strong> Reservations held for 15 minutes. No outside food/drinks.</li>
          <li><strong>Safety & Security:</strong> No smoking in restricted areas. No illegal items.</li>
          <li><strong>Payments & Cancellations:</strong> All payments on check-out. Late cancellations may incur charges.</li>
          <li><strong>Conduct:</strong> Respect staff & guests. Misconduct may lead to eviction.</li>
        </ul>
      </div>

      <!-- Visitor Check-in Form -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Visitor Check-in Form</h2>
        <form id="visitorForm" method="POST" class="space-y-4">
          <input type="hidden" name="action" value="checkin">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name *</label>
              <input type="text" id="full_name" name="full_name" required 
                     class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                     value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
              <div id="full_name-error" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
              <input type="email" id="email" name="email" required 
                     class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                     value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
              <div id="email-error" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
          </div>
          
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="tel" id="phone" name="phone" 
                   class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Enter your phone number"
                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            <div id="phone-error" class="text-red-500 text-sm mt-1 hidden"></div>
          </div>
          
          <div>
            <label for="special_notes" class="block text-sm font-medium text-gray-700">Special Notes (e.g., allergies)</label>
            <textarea id="special_notes" name="special_notes" rows="3" 
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Any special requirements, allergies, or additional information..."><?php echo htmlspecialchars($_POST['special_notes'] ?? ''); ?></textarea>
          </div>
          
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input id="agreement" name="agreement" type="checkbox" required 
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
              </div>
              <div class="ml-3 text-sm">
                <label for="agreement" class="font-medium text-gray-700 cursor-pointer">
                  <i data-lucide="shield-check" class="w-4 h-4 inline mr-1"></i>
                  I agree to the Terms & Conditions *
                </label>
                <p class="text-gray-600 mt-1">You must agree to our terms to proceed with check-in.</p>
              </div>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <button type="submit" id="submitBtn" disabled
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 cursor-not-allowed opacity-50 font-medium flex items-center justify-center">
              <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
              <span>Check In</span>
            </button>
            <button type="reset" 
                    class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 font-medium flex items-center justify-center">
              <i data-lucide="refresh-cw" class="w-5 h-5 mr-2"></i>
              <span>Clear Form</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Visitor Logs Monitoring Section -->
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">Visitor Logs Monitoring</h2>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <span class="text-xs text-gray-600">Active</span>
            <div class="w-3 h-3 bg-gray-400 rounded-full ml-2"></div>
            <span class="text-xs text-gray-600">Checked Out</span>
          </div>
        </div>

        <!-- Filter and Search Controls -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex flex-col md:flex-row gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2">
                  <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Visitors</option>
                  <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                  <option value="checked_out" <?= $filter === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                  <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today Only</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Visitors</label>
                <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." 
                       value="<?= htmlspecialchars($search) ?>"
                       class="border border-gray-300 rounded-md px-3 py-2 w-64">
              </div>
            </div>
            
            <div class="flex gap-2">
              <button id="applyFiltersBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                🔍 Apply Filters
              </button>
              <button id="clearFiltersBtn" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                🗑️ Clear
              </button>
            </div>
          </div>
        </div>

        <!-- Visitor Logs Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
              <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Visitor Info</th>
                <th class="px-6 py-3">Contact</th>
                <th class="px-6 py-3">Check-in Time</th>
                <th class="px-6 py-3">Duration</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Notes</th>
                <th class="px-6 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($visitorLogsResult && $visitorLogsResult->num_rows > 0) : ?>
                <?php while ($row = $visitorLogsResult->fetch_assoc()) : ?>
                  <?php
                  $checkinTime = strtotime($row['submitted_at']);
                  $checkoutTime = $row['checked_out_at'] ? strtotime($row['checked_out_at']) : time();
                  $duration = $checkoutTime - $checkinTime;
                  $hours = floor($duration / 3600);
                  $minutes = floor(($duration % 3600) / 60);
                  $durationText = $row['checked_out'] ? sprintf('%dh %dm', $hours, $minutes) : 'Ongoing';
                  ?>
                  <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900"><?= sprintf('%03d', $row['id']) ?></td>
                    <td class="px-6 py-4">
                      <div>
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></div>
                        <div class="text-sm text-gray-500">ID: <?= sprintf('%03d', $row['id']) ?></div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-gray-900"><?= htmlspecialchars($row['email']) ?></div>
                        <?php if ($row['phone']): ?>
                          <div class="text-sm text-gray-500"><?= htmlspecialchars($row['phone']) ?></div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-green-600 font-medium">
                      <?= date('Y-m-d H:i', strtotime($row['submitted_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm <?= $row['checked_out'] ? 'text-gray-600' : 'text-green-600 font-medium' ?>">
                        <?= $durationText ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <?php if (!$row['checked_out']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                          Active
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          <div class="w-2 h-2 bg-gray-500 rounded-full mr-1"></div>
                          Checked Out
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="max-w-xs truncate">
                        <?= htmlspecialchars($row['notes'] ?: 'No notes') ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <?php if (!$row['checked_out']): ?>
                        <a href="?action=checkout&id=<?= $row['id'] ?>" 
                           class="font-medium text-red-600 hover:text-red-800 hover:underline">
                          Check Out
                        </a>
                      <?php else: ?>
                        <span class="font-medium text-gray-400">Completed</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else : ?>
                <tr>
                  <td colspan="8" class="text-center py-8 text-gray-500">
                    <div class="flex flex-col items-center">
                      <div class="text-4xl mb-2">📋</div>
                      <div>No visitor logs found.</div>
                      <div class="text-sm text-gray-400 mt-1">Try adjusting your filters or check back later.</div>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
          <div>
            Showing <?= $visitorLogsResult ? $visitorLogsResult->num_rows : 0 ?> records
          </div>
          <div class="text-xs">
            Last updated: <?= date('Y-m-d H:i:s') ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Enhanced Form Validation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Lucide icons
      lucide.createIcons();
      
      const agreementCheckbox = document.getElementById('agreement');
      const submitBtn = document.getElementById('submitBtn');
      const visitorForm = document.getElementById('visitorForm');
      const formFields = visitorForm.querySelectorAll('input, textarea');

      // Real-time validation
      formFields.forEach(field => {
        field.addEventListener('blur', function() {
          validateField(this);
        });
        
        field.addEventListener('input', function() {
          clearFieldError(this);
        });
      });

      // Enable/disable submit button based on agreement checkbox
      agreementCheckbox?.addEventListener('change', function() {
        updateSubmitButton();
      });

      // Form validation on submit
      visitorForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let hasErrors = false;
        
        // Validate all fields
        formFields.forEach(field => {
          if (validateField(field)) {
            hasErrors = true;
          }
        });

        // Validate agreement
        if (!agreementCheckbox.checked) {
          showToast('You must agree to the Terms & Conditions to proceed.', 'error');
          hasErrors = true;
        }

        if (!hasErrors) {
          // Show loading state
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 mr-2 animate-spin"></i><span>Processing...</span>';
          lucide.createIcons();
          
          // Submit form
          setTimeout(() => {
            visitorForm.submit();
          }, 500);
        }
      });

      function validateField(field) {
        const fieldId = field.id;
        const value = field.value.trim();
        let hasError = false;
        let errorMessage = '';

        switch (fieldId) {
          case 'full_name':
            if (!value) {
              errorMessage = 'Full name is required';
              hasError = true;
            } else if (value.length < 2) {
              errorMessage = 'Full name must be at least 2 characters';
              hasError = true;
            } else if (!/^[a-zA-Z\s]+$/.test(value)) {
              errorMessage = 'Full name can only contain letters and spaces';
              hasError = true;
            }
            break;
            
          case 'email':
            if (!value) {
              errorMessage = 'Email address is required';
              hasError = true;
            } else if (!isValidEmail(value)) {
              errorMessage = 'Please enter a valid email address';
              hasError = true;
            }
            break;
            
          case 'phone':
            if (value && !isValidPhone(value)) {
              errorMessage = 'Please enter a valid phone number';
              hasError = true;
            }
            break;
        }

        if (hasError) {
          showFieldError(fieldId, errorMessage);
          field.classList.add('border-red-500', 'error-shake');
        } else {
          clearFieldError(fieldId);
          field.classList.remove('border-red-500', 'error-shake');
        }

        return hasError;
      }

      function showFieldError(fieldId, message) {
        const errorDiv = document.getElementById(fieldId + '-error');
        if (errorDiv) {
          const span = errorDiv.querySelector('span');
          if (span) span.textContent = message;
          errorDiv.classList.remove('hidden');
        }
      }

      function clearFieldError(fieldId) {
        const errorDiv = document.getElementById(fieldId + '-error');
        if (errorDiv) {
          errorDiv.classList.add('hidden');
        }
      }

      function updateSubmitButton() {
        if (agreementCheckbox.checked) {
          submitBtn.disabled = false;
          submitBtn.classList.remove('cursor-not-allowed', 'opacity-50');
          submitBtn.classList.add('hover:bg-blue-700');
        } else {
          submitBtn.disabled = true;
          submitBtn.classList.add('cursor-not-allowed', 'opacity-50');
          submitBtn.classList.remove('hover:bg-blue-700');
        }
      }

      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      }

      function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,15}$/;
        return phoneRegex.test(phone);
      }

      function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
          type === 'error' ? 'bg-red-100 text-red-700 border border-red-300' : 
          type === 'success' ? 'bg-green-100 text-green-700 border border-green-300' :
          'bg-blue-100 text-blue-700 border border-blue-300'
        }`;
        toast.innerHTML = `
          <div class="flex items-center">
            <i data-lucide="${type === 'error' ? 'alert-circle' : type === 'success' ? 'check-circle' : 'info'}" class="w-5 h-5 mr-2"></i>
            ${message}
          </div>
        `;
        
        document.body.appendChild(toast);
        lucide.createIcons();
        
        setTimeout(() => {
          toast.remove();
        }, 3000);
      }

      // Auto-hide success/error messages
      const successMsg = document.getElementById('successMessage');
      const errorMsg = document.getElementById('errorMessage');
      
      if (successMsg) {
        setTimeout(() => successMsg.remove(), 5000);
      }
      if (errorMsg) {
        setTimeout(() => errorMsg.remove(), 7000);
      }

      // Visitor Monitoring Features
      const refreshDataBtn = document.getElementById('refreshDataBtn');
      const applyFiltersBtn = document.getElementById('applyFiltersBtn');
      const clearFiltersBtn = document.getElementById('clearFiltersBtn');
      const statusFilter = document.getElementById('statusFilter');
      const searchInput = document.getElementById('searchInput');

      // Refresh data functionality
      refreshDataBtn?.addEventListener('click', function() {
        this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Refreshing...';
        lucide.createIcons();
        
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      });

      // Apply filters functionality
      applyFiltersBtn?.addEventListener('click', function() {
        const filter = statusFilter.value;
        const search = searchInput.value.trim();
        
        let url = window.location.pathname;
        const params = new URLSearchParams();
        
        if (filter !== 'all') {
          params.append('filter', filter);
        }
        
        if (search) {
          params.append('search', search);
        }
        
        if (params.toString()) {
          url += '?' + params.toString();
        }
        
        window.location.href = url;
      });

      // Clear filters functionality
      clearFiltersBtn?.addEventListener('click', function() {
        statusFilter.value = 'all';
        searchInput.value = '';
        window.location.href = window.location.pathname;
      });

      // Auto-apply filters on Enter key in search
      searchInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          applyFiltersBtn.click();
        }
      });

      // Real-time search suggestions (optional enhancement)
      let searchTimeout;
      searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          // Could add live search functionality here
        }, 300);
      });

      // Auto-refresh data every 30 seconds for real-time monitoring
      setInterval(() => {
        // Only auto-refresh if no user interaction in the last 10 seconds
        if (document.hidden === false) {
          const lastInteraction = localStorage.getItem('lastUserInteraction');
          const now = Date.now();
          
          if (!lastInteraction || (now - parseInt(lastInteraction)) > 10000) {
            // Silently refresh the page to update data
            window.location.reload();
          }
        }
      }, 30000);

      // Track user interactions to prevent auto-refresh during active use
      ['click', 'keypress', 'scroll'].forEach(event => {
        document.addEventListener(event, () => {
          localStorage.setItem('lastUserInteraction', Date.now().toString());
        });
      });

      // Add monitoring alerts for high visitor counts
      const activeVisitors = <?= $stats['active_visitors'] ?>;
      if (activeVisitors > 10) {
        showToast(`High visitor activity: ${activeVisitors} active visitors`, 'info');
      }
    });
  </script>
</body>
</html>
