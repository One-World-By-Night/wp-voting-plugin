// Make this function global or at the same scope as `addingVotingOption`
let draggedItem = null; // Declare draggedItem in the global scope
const container = document.getElementById('anoteritembox'); // Declare container in the global scope

function addDragAndDropListeners(item) {
  item.setAttribute('draggable', 'true');

  // Drag start
  item.addEventListener('dragstart', function (e) {
      draggedItem = item;
      setTimeout(() => {
          item.classList.add('dragging');
      }, 0);
  });

  // Drag end
  item.addEventListener('dragend', function () {
      draggedItem = null;
      item.classList.remove('dragging');
  });

  // Drag over
  item.addEventListener('dragover', function (e) {
      e.preventDefault();
      const target = e.target.closest('.cholce-box-inpt');
      if (target && target !== draggedItem) {
          target.classList.add('drag-over');
      }
  });

  // Drag leave
  item.addEventListener('dragleave', function (e) {
      const target = e.target.closest('.cholce-box-inpt');
      if (target) {
          target.classList.remove('drag-over');
      }
  });

  // Drop
  item.addEventListener('drop', function (e) {
      e.preventDefault();
      const target = e.target.closest('.cholce-box-inpt');
      if (target && target !== draggedItem) {
          const allItems = Array.from(container.children);
          const draggedIndex = allItems.indexOf(draggedItem);
          const targetIndex = allItems.indexOf(target);

          target.classList.remove('drag-over');

          if (draggedIndex < targetIndex) {
              container.insertBefore(draggedItem, target.nextSibling);
          } else {
              container.insertBefore(draggedItem, target);
          }
      }
  });
}
document.addEventListener('DOMContentLoaded', function() {
  
  document.querySelectorAll('.cholce-box-inpt').forEach(addDragAndDropListeners);
});


/**
 * Add an input element to the voting options and call addingToOptions with the
 */
function addingVotingOption() {
  // Generate a unique short number
  var shortNumber = Math.random().toString(36).substring(2, 8); // Example: "abc123"

  // Create the input element
  var input = document.createElement("input");
  input.type = "text";
  input.id = "input_" + shortNumber; // Assign a unique ID
  input.onblur = function () {
    addingToOptions(this.id, this.value); // Call addingToOptions with the input value when blurred
  };

  var deleteBtn = document.createElement("button");
deleteBtn.classList.add("delete-btn");
deleteBtn.innerHTML = "−"; // Set the delete button text
deleteBtn.onclick = function () {
    removeOption(input.id); // Call remove function with the input ID
};

  // Create the container div for the input element
  var div = document.createElement("div");
  div.classList.add("cholce-box-inpt");
  div.setAttribute('draggable', 'true');

  // Append the input element to the container div
  div.appendChild(input);
  div.appendChild(deleteBtn);

  // Append the container div to the parent container
  var votingOptionsContainer = document.getElementById("anoteritembox");

  //// Yash Comment 25-09-2K24
  //votingOptionsContainer.appendChild(div);
  votingOptionsContainer.prepend(div);

  addDragAndDropListeners(div);
}


