document.addEventListener("DOMContentLoaded", function() {
  var actions = document.querySelectorAll("[id*='action-']");
  actions.forEach(function(action) {
    var actionId = action.getAttribute("id").split("-")[1];
    var reaction = "reaction-" + actionId;
    var reactionEl = document.getElementById(reaction);
    var substitude = "substitute-" + actionId;
    var substitudeEl = document.getElementById(substitude);
    if (reactionEl && substitudeEl) {
      action.addEventListener("mouseover", function() {
        reactionEl.style.display = "block";
        substitudeEl.style.display = "none";
      });
      action.addEventListener("mouseleave", function() {
        reactionEl.style.display = "none";
        substitudeEl.style.display = "block";
      });
    }
  });
});
