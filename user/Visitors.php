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
$user = 'admin_visitors';
$pass = '123';
$db   = 'admin_visitors';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Fetch visitor types for dropdown
$visitorTypesResult = $conn->query("SELECT * FROM visitor_types WHERE is_active = 1 ORDER BY type_name");

// Fetch employees for host selection
$employeesResult = $conn->query("SELECT * FROM employees WHERE is_active = 1 ORDER BY full_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'timein') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $special_notes = trim($_POST['special_notes'] ?? '');
    $visitor_type_id = (int)($_POST['visitor_type'] ?? 1);
    $purpose = trim($_POST['purpose'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $host_employee = trim($_POST['host_employee'] ?? '');
    $expected_duration = (int)($_POST['expected_duration'] ?? 60);
    $priority = $_POST['priority'] ?? 'Medium';
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    if (empty($full_name) || empty($email) || !$agreement) {
        $error_message = "Please fill all required fields and agree to terms.";
    } else {
        $sql = "INSERT INTO guest_submissions (full_name, email, phone, notes, visitor_type_id, purpose, company, host_employee, expected_duration, priority, time_in, status, agreement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Time In', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisssis", $full_name, $email, $phone, $special_notes, $visitor_type_id, $purpose, $company, $host_employee, $expected_duration, $priority, $agreement);
        
        if ($stmt->execute()) {
            $success_message = "Thank you for time-in! Welcome to our Hotel & Restaurant.";
        } else {
            $error_message = "Error occurred during time-in. Please try again.";
        }
        $stmt->close();
    }
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <title>Visitor Check-in</title>
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
      <h2 class="text-xl font-semibold text-gray-800">Visitor Check-in</h2>
      <?php include __DIR__ . '/../profile.php'; ?>
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
          <input type="hidden" name="action" value="timein">
          
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

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="visitor_type" class="block text-sm font-medium text-gray-700">Visitor Type *</label>
              <select id="visitor_type" name="visitor_type" required 
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select visitor type...</option>
                <?php if ($visitorTypesResult && $visitorTypesResult->num_rows > 0): ?>
                  <?php while ($type = $visitorTypesResult->fetch_assoc()): ?>
                    <option value="<?= $type['id'] ?>" 
                            <?= (isset($_POST['visitor_type']) && $_POST['visitor_type'] == $type['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($type['type_name']) ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>
            
            <div>
              <label for="company" class="block text-sm font-medium text-gray-700">Company/Organization</label>
              <input type="text" id="company" name="company" 
                     class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                     placeholder="Enter company name"
                     value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
              <input type="tel" id="phone" name="phone" 
                     class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                     placeholder="Enter your phone number"
                     value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
              <div id="phone-error" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
              <label for="host_employee" class="block text-sm font-medium text-gray-700">Host Employee</label>
              <select id="host_employee" name="host_employee" 
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select host employee...</option>
                <?php if ($employeesResult && $employeesResult->num_rows > 0): ?>
                  <?php while ($employee = $employeesResult->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($employee['full_name']) ?>" 
                            <?= (isset($_POST['host_employee']) && $_POST['host_employee'] == $employee['full_name']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($employee['full_name']) ?> (<?= htmlspecialchars($employee['department']) ?>)
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <div>
            <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose of Visit *</label>
            <textarea id="purpose" name="purpose" rows="2" required
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Describe the purpose of your visit..."><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="expected_duration" class="block text-sm font-medium text-gray-700">Expected Duration (minutes)</label>
              <select id="expected_duration" name="expected_duration" 
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="30" <?= (isset($_POST['expected_duration']) && $_POST['expected_duration'] == 30) ? 'selected' : '' ?>>30 minutes</option>
                <option value="60" <?= (isset($_POST['expected_duration']) && $_POST['expected_duration'] == 60) ? 'selected' : '' ?>>1 hour</option>
                <option value="120" <?= (isset($_POST['expected_duration']) && $_POST['expected_duration'] == 120) ? 'selected' : '' ?>>2 hours</option>
                <option value="240" <?= (isset($_POST['expected_duration']) && $_POST['expected_duration'] == 240) ? 'selected' : '' ?>>4 hours</option>
                <option value="480" <?= (isset($_POST['expected_duration']) && $_POST['expected_duration'] == 480) ? 'selected' : '' ?>>8 hours (Full day)</option>
              </select>
            </div>
            
            <div>
              <label for="priority" class="block text-sm font-medium text-gray-700">Priority Level</label>
              <select id="priority" name="priority" 
                      class="w-full mt-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="Low" <?= (isset($_POST['priority']) && $_POST['priority'] == 'Low') ? 'selected' : '' ?>>Low</option>
                <option value="Medium" <?= (isset($_POST['priority']) && $_POST['priority'] == 'Medium') ? 'selected' : '' ?>>Medium</option>
                <option value="High" <?= (isset($_POST['priority']) && $_POST['priority'] == 'High') ? 'selected' : '' ?>>High</option>
                <option value="Urgent" <?= (isset($_POST['priority']) && $_POST['priority'] == 'Urgent') ? 'selected' : '' ?>>Urgent</option>
              </select>
            </div>
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
              <i data-lucide="clock" class="w-5 h-5 mr-2"></i>
              <span>Time In</span>
            </button>
            <button type="reset" 
                    class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 font-medium flex items-center justify-center">
              <i data-lucide="refresh-cw" class="w-5 h-5 mr-2"></i>
              <span>Clear Form</span>
            </button>
          </div>
        </form>
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


    });
  </script>
</body>
</html>
