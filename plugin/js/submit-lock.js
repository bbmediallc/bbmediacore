jQuery(document).ready(function ($) {
    console.log('jQuery is loaded and ready.');

    $('#support-form').on('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        console.log('Form submit event triggered.');

        var $form = $(this);
        var formData = $form.serialize(); // Serialize form data for submission
        var submitButton = $form.find('input[type="submit"]');

        // Show a loading state
        submitButton.prop('disabled', true).val('Submitting...');

        // Send the form data via AJAX
        $.post($form.attr('action'), formData, function (response) {
            console.log('Server response:', response);

            // Check if the response is structured correctly
            if (response && response.success && response.data && response.data.message) {
                // Success: Hide the form and display the success message
                $form[0].reset(); // Clear all form fields
                $form.hide();
                $('#form-message')
                    .html('<div class="success-message">' + response.data.message + '</div>')
                    .fadeIn();
            } else {
                // Handle unexpected success response without a message
                console.error('Unexpected server response structure:', response);

                $('#form-message')
                    .html('<div class="error-message">An unexpected error occurred. Please try again.</div>')
                    .fadeIn();

                // Re-enable the submit button
                submitButton.prop('disabled', false).val('Submit Support Ticket');
            }
        }).fail(function (xhr, status, error) {
            console.error('AJAX error:', status, error);

            // Generic error message
            $('#form-message')
                .html('<div class="error-message">There was an error submitting your ticket. Please try again later.</div>')
                .fadeIn();

            // Re-enable the submit button
            submitButton.prop('disabled', false).val('Submit Support Ticket');
        });
    });
});