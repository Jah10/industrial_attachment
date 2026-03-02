// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop over forms and prevent submission if they're invalid
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        });
    }
    
    // Add date picker to date fields
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        input.valueAsDate = input.valueAsDate || new Date();
    });
    
    // Dynamic form elements
    const addWeekButton = document.getElementById('add-week');
    if (addWeekButton) {
        addWeekButton.addEventListener('click', function() {
            const activitiesContainer = document.getElementById('activities-container');
            const weekCount = activitiesContainer.children.length + 1;
            
            const weekDiv = document.createElement('div');
            weekDiv.className = 'card mb-3';
            weekDiv.innerHTML = `
                <div class="card-header">Week ${weekCount}</div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="start_date_${weekCount}">Start Date</label>
                        <input type="date" class="form-control" id="start_date_${weekCount}" name="start_date[]" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date_${weekCount}">End Date</label>
                        <input type="date" class="form-control" id="end_date_${weekCount}" name="end_date[]" required>
                    </div>
                    <div class="form-group">
                        <label for="activities_${weekCount}">Activities</label>
                        <textarea class="form-control" id="activities_${weekCount}" name="activities[]" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="challenges_${weekCount}">Challenges</label>
                        <textarea class="form-control" id="challenges_${weekCount}" name="challenges[]" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="solutions_${weekCount}">Solutions</label>
                        <textarea class="form-control" id="solutions_${weekCount}" name="solutions[]" rows="2"></textarea>
                    </div>
                </div>
            `;
            
            activitiesContainer.appendChild(weekDiv);
        });
    }
    
    // Show/hide fields based on user type selection
    const userTypeSelect = document.getElementById('user_type');
    if (userTypeSelect) {
        const studentFields = document.getElementById('student-fields');
        const organizationFields = document.getElementById('organization-fields');
        const supervisorFields = document.getElementById('supervisor-fields');
        
        userTypeSelect.addEventListener('change', function() {
            // Hide all fields first
            if (studentFields) studentFields.style.display = 'none';
            if (organizationFields) organizationFields.style.display = 'none';
            if (supervisorFields) supervisorFields.style.display = 'none';
            
            // Show the appropriate fields
            if (this.value === 'student' && studentFields) {
                studentFields.style.display = 'block';
            } else if (this.value === 'organization' && organizationFields) {
                organizationFields.style.display = 'block';
            } else if (this.value === 'supervisor' && supervisorFields) {
                supervisorFields.style.display = 'block';
            }
        });
        
        // Trigger change event to set initial state
        userTypeSelect.dispatchEvent(new Event('change'));
    }
    
    // Initialize any tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Wait for document to load
$(document).ready(function() {
    // Enable all tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Enable all popovers
    $('[data-toggle="popover"]').popover();
    
    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $($(this).attr('toggle'));
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).toggleClass('fa-eye fa-eye-slash');
        } else {
            input.attr('type', 'password');
            $(this).toggleClass('fa-eye-slash fa-eye');
        }
    });
    
    // Student assessment form - auto calculate total
    if ($('#assessment-form').length) {
        $('input[type="radio"]').on('change', function() {
            let total = 0;
            
            // Get all selected ratings
            $('input[type="radio"]:checked').each(function() {
                total += parseInt($(this).val());
            });
            
            // Update total score display
            $('#total-score').text(total);
        });
    }
    
    // Pre-select student in assessment form when coming from dashboard link
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('student_id');
    if (studentId) {
        $('#student_id').val(studentId);
    }
});