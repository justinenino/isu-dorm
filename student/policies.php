<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$page_title = "Dorm Policies";
require_once 'includes/header.php';

// Get policies
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM policies 
        WHERE status = 'active' 
        ORDER BY policy_type, created_at DESC
    ");
    $stmt->execute();
    $policies = $stmt->fetchAll();
    
    // Group policies by type
    $grouped_policies = [];
    foreach ($policies as $policy) {
        $grouped_policies[$policy['policy_type']][] = $policy;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $policies = [];
    $grouped_policies = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-white">Dorm Policies</h1>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Policy Navigation -->
                <div class="col-lg-3">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Policy Categories</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="#general" class="list-group-item list-group-item-action">
                                    <i class="fas fa-home me-2"></i>General Rules
                                </a>
                                <a href="#conduct" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user-check me-2"></i>Code of Conduct
                                </a>
                                <a href="#safety" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shield-alt me-2"></i>Safety & Security
                                </a>
                                <a href="#visitors" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user-friends me-2"></i>Visitor Policies
                                </a>
                                <a href="#maintenance" class="list-group-item list-group-item-action">
                                    <i class="fas fa-tools me-2"></i>Maintenance & Facilities
                                </a>
                                <a href="#offenses" class="list-group-item list-group-item-action">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Offenses & Penalties
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Need Help?</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                If you have questions about any policy, please contact the dormitory administration.
                            </p>
                            
                            <div class="mb-2">
                                <strong>Office Hours:</strong><br>
                                Monday - Friday: 8:00 AM - 5:00 PM
                            </div>
                            <div class="mb-2">
                                <strong>Emergency Contact:</strong><br>
                                Available 24/7
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Policy Content -->
                <div class="col-lg-9">
                    <?php if (empty($grouped_policies)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No policies are currently available.</p>
                                    <small class="text-muted">Policies will be displayed here once they are published by the administration.</small>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- General Rules -->
                        <div id="general" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-home me-2"></i>General Rules
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['general'])): ?>
                                    <?php foreach ($grouped_policies['general'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No general rules available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Code of Conduct -->
                        <div id="conduct" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-user-check me-2"></i>Code of Conduct
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['conduct'])): ?>
                                    <?php foreach ($grouped_policies['conduct'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No conduct policies available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Safety & Security -->
                        <div id="safety" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-shield-alt me-2"></i>Safety & Security
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['safety'])): ?>
                                    <?php foreach ($grouped_policies['safety'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No safety policies available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Visitor Policies -->
                        <div id="visitors" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-user-friends me-2"></i>Visitor Policies
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['visitors'])): ?>
                                    <?php foreach ($grouped_policies['visitors'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No visitor policies available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Maintenance & Facilities -->
                        <div id="maintenance" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-tools me-2"></i>Maintenance & Facilities
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['maintenance'])): ?>
                                    <?php foreach ($grouped_policies['maintenance'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No maintenance policies available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Offenses & Penalties -->
                        <div id="offenses" class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Offenses & Penalties
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($grouped_policies['offenses'])): ?>
                                    <?php foreach ($grouped_policies['offenses'] as $policy): ?>
                                        <div class="policy-item mb-4">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($policy['title']); ?></h6>
                                            <div class="policy-content">
                                                <?php echo nl2br(htmlspecialchars($policy['content'])); ?>
                                            </div>
                                            <?php if ($policy['file_path']): ?>
                                                <div class="mt-2">
                                                    <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> Download PDF
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Last updated: <?php echo date('F j, Y', strtotime($policy['updated_at'] ?: $policy['created_at'])); ?></small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Common Offenses</h6>
                                        <ul class="mb-0">
                                            <li><strong>Minor Offenses:</strong> Noise violations, improper dress code, late curfew</li>
                                            <li><strong>Major Offenses:</strong> Property damage, unauthorized visitors, substance abuse</li>
                                            <li><strong>Severe Offenses:</strong> Violence, theft, repeated violations</li>
                                        </ul>
                                    </div>
                                    <p class="text-muted">Detailed offense policies will be displayed here once published.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Important Notice -->
            <div class="card shadow mb-4 border-left-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div class="col">
                            <h6 class="text-warning mb-2">Important Notice</h6>
                            <p class="mb-0">
                                All students are expected to read, understand, and comply with these policies. 
                                Ignorance of the rules is not an excuse for violations. Policies may be updated 
                                periodically, so please check this page regularly for any changes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Smooth scrolling for navigation links
    $('.list-group-item').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        if ($(target).length) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 500);
            
            // Update active state
            $('.list-group-item').removeClass('active');
            $(this).addClass('active');
        }
    });
    
    // Highlight current section on scroll
    $(window).on('scroll', function() {
        const scrollPos = $(window).scrollTop() + 150;
        
        $('.card[id]').each(function() {
            const currentElement = $(this);
            const currentElementTop = currentElement.offset().top;
            const currentElementBottom = currentElementTop + currentElement.outerHeight();
            
            if (scrollPos >= currentElementTop && scrollPos <= currentElementBottom) {
                const id = currentElement.attr('id');
                $('.list-group-item').removeClass('active');
                $('.list-group-item[href="#' + id + '"]').addClass('active');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
