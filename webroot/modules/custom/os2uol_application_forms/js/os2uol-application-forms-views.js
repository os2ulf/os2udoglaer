(function ($, Drupal) {
  Drupal.behaviors.os2uolApplicationFormsViews = {
    attach: function (context, settings) {
      if (drupalSettings.os2uol_application_forms?.school_budget_overview) {
        let allocated_budget = drupalSettings.os2uol_application_forms.school_budget_overview.allocated_budget;
        let spent_budget = drupalSettings.os2uol_application_forms.school_budget_overview.spent_budget;
        let remaining_budget = drupalSettings.os2uol_application_forms.school_budget_overview.remaining_budget;

        let allocated_budget_element = document.getElementById('applications-summary__value--allocated');
        let spent_budget_element = document.getElementById('applications-summary__value--spent');
        let remaining_budget_element = document.getElementById('applications-summary__value--remaining');

        allocated_budget_element.innerHTML = allocated_budget.toLocaleString('da-DK') + ' kr.';
        spent_budget_element.innerHTML = spent_budget.toLocaleString('da-DK') + ' kr.';
        remaining_budget_element.innerHTML = remaining_budget.toLocaleString('da-DK') + ' kr.';
      }
    }
  };
})(jQuery, Drupal);
