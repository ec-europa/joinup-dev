(function ($, Drupal, cookies, drupalSettings) {
  // This is firing on document ready instead of using Drupal behaviors since
  // we only want to check this on initial page load, not on AJAX requests.
  $(document).ready(() => {

    // When an anonymous user indicates that they want to join a collection a
    // cookie will be set that tracks which collection the user wants to join,
    // so that we can show a dialog urging the user to subscribe to email
    // notifications.
    // If the cookie is set, do an AJAX request to show the subscribe form.
    const encoded_collection_id = cookies.get('join_group');
    if (!!encoded_collection_id) {
      try {
        const collection_id = decodeURIComponent(encoded_collection_id);
        if (collection_id === drupalSettings.joinGroupData.id) {
          const sparql_encoded_id = drupalSettings.joinGroupData.sparqlEncodedId;
          if (!!sparql_encoded_id) {
            Drupal.ajax({
              url: drupalSettings.path.baseUrl + 'ajax/collection/' + sparql_encoded_id + '/subscribe/auth',
            }).execute();
          }
        }
      }
      catch (err) {
        // Collection ID was not URL encoded. Remove the invalid cookie.
        cookies.remove('join_group');
      }
    }
  });
})(jQuery, Drupal, window.Cookies, drupalSettings);
