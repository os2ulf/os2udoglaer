jQuery(function($){
  // Set variables for details elements in user form.
  const userFormDetails = $('form.user-form details');
  let currentDetails;

  // Loop through details elements in user form.
  for (let i = 0; i < userFormDetails.length; i++) {
    currentDetails = userFormDetails[i];
    if (currentDetails.children.length <= 1) {
      // Hide details element if it only contains one element.
      currentDetails.style.display = 'none';
    }
  }
});
