(function (Drupal, once) {
  Drupal.behaviors.os2uolAdminNumberInput = {
    attach(context) {
      once('os2uol-admin-number-input', 'input[type="number"]', context).forEach((input) => {
        input.addEventListener('keydown', (event) => {
          if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
          }
        });
      });
    },
  };
})(Drupal, once);
