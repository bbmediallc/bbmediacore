jQuery(document).ready(function ($) {
    $('#support-form').on('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        var $form = $(this);
        var formData = $form.serialize(); // Serialize form data for submission
        var submitButton = $form.find('input[type="submit"]');

        function showMessage(type, text) {
            $('#form-message')
                .empty()
                .append($('<div>').addClass(type + '-message').text(text))
                .fadeIn();
        }

        // Show a loading state
        submitButton.prop('disabled', true).val('Submitting...');

        // Send the form data via AJAX
        $.post($form.attr('action'), formData, function (response) {
            if (response && response.success && response.data && response.data.message) {
                // Success: Hide the form and display the success message
                $form[0].reset(); // Clear all form fields
                $form.hide();
                showMessage('success', response.data.message);
            } else {
                var message = response && response.data && response.data.message
                    ? response.data.message
                    : 'An unexpected error occurred. Please try again.';
                showMessage('error', message);

                // Re-enable the submit button
                submitButton.prop('disabled', false).val('Submit Support Ticket');
            }
        }).fail(function () {
            showMessage('error', 'There was an error submitting your ticket. Please try again later.');

            // Re-enable the submit button
            submitButton.prop('disabled', false).val('Submit Support Ticket');
        });
    });
});
