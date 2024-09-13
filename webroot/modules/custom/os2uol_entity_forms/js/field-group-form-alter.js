jQuery(function($){
  fieldGroupFormAlter();
  $(document).ajaxComplete(function(){
    fieldGroupFormAlter();
  });

  function fieldGroupFormAlter() {
    // Set variables for details elements in all forms.
    const userFormDetails = $('form details');
    let currentDetails;

    // Loop through details elements in all forms.
    for (let i = 0; i < userFormDetails.length; i++) {
      currentDetails = userFormDetails[i];
      if (currentDetails.children.length <= 1) {
        // Hide details element if it only contains one element.
        currentDetails.style.display = 'none';
      }
    }
  }
});
