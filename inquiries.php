<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['user_id'];
$userName = user_display_name($user);
$userEmail = (string) ($user['email'] ?? '');

$pageTitle = 'Inquiries';
$activeNav = 'inquiries';
$extraScripts = [
    ['src' => asset('js/firebase-config.js')],
    ['src' => asset('js/inquiries.js'), 'type' => 'module'],
];

require __DIR__ . '/includes/header.php';
?>

<div class="container inquiries-page"
     id="inquiriesApp"
     data-user-id="<?= (int) $userId ?>"
     data-user-name="<?= e($userName) ?>"
     data-user-email="<?= e($userEmail) ?>">
    <div class="page-head">
        <h1>Device Inquiries</h1>
        <p>
            Need help with borrowing, listing, or your account? Submit an inquiry here.
            Choose a category, write a short subject and message, then track its status below.
            You can update or close an inquiry anytime.
        </p>
    </div>

    <div id="inquirySetupNote" class="alert alert-warning" hidden>
        Inquiries are temporarily unavailable. Please try again later or contact support.
    </div>

    <div id="inquiryAlert" class="alert" hidden></div>

    <div class="inquiries-layout">
        <section class="card inquiry-form-card">
            <h2>Create inquiry</h2>
            <p class="text-muted">Tell us what you need help with. We’ll keep your request in your inquiry list so you can follow up.</p>

            <form id="inquiryCreateForm" class="inquiry-form">
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="fullName">Full name</label>
                        <input class="form-control" type="text" id="fullName" name="fullName" required maxlength="80">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" required maxlength="120">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="Borrow Help">Borrow Help</option>
                            <option value="Listing Help">Listing Help</option>
                            <option value="Account Help">Account Help</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Open" selected>Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input class="form-control" type="text" id="subject" name="subject" required maxlength="120" placeholder="Short summary of your request">
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required maxlength="2000" placeholder="Describe what you need help with…"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit inquiry</button>
            </form>
        </section>

        <section class="card inquiry-edit-card" id="inquiryEditPanel" hidden>
            <h2>Edit inquiry</h2>
            <form id="inquiryEditForm" class="inquiry-form">
                <input type="hidden" id="editInquiryId" value="">
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="editCategory">Category</label>
                        <select class="form-select" id="editCategory" required>
                            <option value="Borrow Help">Borrow Help</option>
                            <option value="Listing Help">Listing Help</option>
                            <option value="Account Help">Account Help</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select class="form-select" id="editStatus" required>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editSubject">Subject</label>
                    <input class="form-control" type="text" id="editSubject" required maxlength="120">
                </div>
                <div class="form-group">
                    <label for="editMessage">Message</label>
                    <textarea class="form-control" id="editMessage" rows="4" required maxlength="2000"></textarea>
                </div>
                <div class="inquiry-edit-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
                    <button type="button" class="btn btn-muted btn-sm" id="inquiryEditCancel">Cancel</button>
                </div>
            </form>
        </section>
    </div>

    <section class="card inquiry-list-card">
        <div class="inquiry-toolbar">
            <div>
                <h2>Your inquiries</h2>
                <p class="text-muted mb-0" id="inquiryCount">0 shown</p>
            </div>
            <div class="inquiry-filters">
                <label>
                    Status
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </label>
                <label>
                    Category
                    <select class="form-select form-select-sm" id="filterCategory">
                        <option value="">All</option>
                        <option value="Borrow Help">Borrow Help</option>
                        <option value="Listing Help">Listing Help</option>
                        <option value="Account Help">Account Help</option>
                        <option value="Technical Issue">Technical Issue</option>
                        <option value="Other">Other</option>
                    </select>
                </label>
                <label>
                    Sort
                    <select class="form-select form-select-sm" id="sortDir">
                        <option value="desc" selected>Newest first</option>
                        <option value="asc">Oldest first</option>
                    </select>
                </label>
                <button type="button" class="btn btn-sm btn-muted" id="refreshInquiries">Refresh</button>
            </div>
        </div>

        <div id="inquiryEmpty" class="inquiries-empty text-muted" hidden>
            No inquiries match your filters yet. Create one above.
        </div>
        <div id="inquiryList" class="inquiry-list"></div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
