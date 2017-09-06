/**
 * @file
 */

$(document).ready(function () {
var actions = $("[id*='action-']");
if (actions.length) {
  actions.hover(
    function () {
      var action_id = $(this).attr("id").split('-')[1];
      var reaction_id = "#reaction-" + action_id;
      var substitute_id = "#substitute-" + action_id;
      if ($(reaction_id).length && $(substitute_id).length) {
        $(reaction_id).show();
        $(substitute_id).hide();
      }
    },
    function () {
      var action_id = $(this).attr("id").split('-')[1];
      var reaction_id = "#reaction-" + action_id;
      var substitute_id = "#substitute-" + action_id;
      if ($(reaction_id).length && $(substitute_id).length) {
        $(reaction_id).hide();
        $(substitute_id).show();
      }
    }
  );
}
});
