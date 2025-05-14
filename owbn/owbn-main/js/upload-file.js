var votingOptions = [];

/**
 * prepare the data to send over ajax handler
 *
 *
 * @return { void } Returns
 */
function uploadFile() {
  console.log("a");
  console.log(uploadedFiles);
  var fileInput = document.getElementById("uploadFile");
  
  // Check if a file is selected
  if (fileInput.files.length > 0) {
    var file = fileInput.files[0];
    console.log(file.name);

    // Check if file size exceeds the limit of 20MB.
    if (file.size > 20 * 1024 * 1024) {
      // Convert MB to bytes
      alert("File size exceeds the limit of 20 MB.");
      return false;
    }

    // Check file type
    var allowedTypes = [".txt", ".rtf", ".doc", ".pdf"];
    var fileType = file.name
      .substring(file.name.lastIndexOf("."))
      .toLowerCase();
    // Checks if the file type is allowed
    if (!allowedTypes.includes(fileType)) {
      alert(
        "File type is not allowed. Allowed types are: .txt, .rtf, .doc, and .pdf"
      );
      return false;
    }

    // Check if the file already exists in the uploadedFiles array
    /**
     * @param uploadedFile
     *
     * @return { boolean } True if the file belongs to this
     */
    var fileNameExists = uploadedFiles.some(function (uploadedFile) {
      return uploadedFile.fileName === file.name;
    });

    // if fileNameExists is true alerts if the same file exists
    if (fileNameExists) {
      console.log("i");
      alert("Same file Exists");
      return false;
    }
    
    var fileInfoTable = document
    .getElementById("fileInfoTable")
    .getElementsByTagName("tbody")[0];

  // Clear existing rows from the table
  fileInfoTable.innerHTML = "";
  console.log(fileInfoTable);

    // If the file does not exist, upload it to WordPress
    var formData = new FormData();
    formData.append("file", file);
    formData.append("action", "upload_file");
    formData.append("security", my_ajax_obj.nonce);

    fetch(my_ajax_obj.ajax_url, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((res) => {
        // Upload the file to the file table
        if (res.success) {
          console.log("File uploaded successfully");
          
          // Add the file to the uploadedFiles array
          uploadedFiles.push({
            id: res.attachment_id,
            fileName: file.name,
            fileSize: file.size,
            fileType: fileType,
            description: "",
            display: false,
            url: res.url,
          });

          // Clear the file input
          fileInput.value = "";

          /**
           * Iterate over the uploadedFiles array and insert new rows
           *
           * @param uploadedFile - Contains information about the file that has been uploaded
           *
           * @return { Object } Contains information about the file that has been uploaded to the
           */
          uploadedFiles.forEach(function (uploadedFile,index) {
            // Check if any row in the table already has the same file name
            /**
             * @param row
             *
             * @return { boolean } True if the row contains the
             */
            var fileNameExists = Array.from(fileInfoTable.rows).some(function (
              row
            ) {
              return row.cells[0].innerText.includes(uploadedFile.fileName);
            });

            // If a row with the same file name does not exist, add the file to the table
            if (!fileNameExists) {
              // Create a new row for the table
              var newRow = fileInfoTable.insertRow();

              // Insert cells into the new row
              var fileNameCell = newRow.insertCell(0);
              var displayCell = newRow.insertCell(1);
              var operationsCell = newRow.insertCell(2);
             
              // Set the file name, size, and description
              fileNameCell.innerHTML =
                '<div class="file--name"><p><a target="_blank" href="'+uploadedFile.url+'">' +
                uploadedFile.fileName +
                "<span>(" +
                uploadedFile.fileSize +
                " bytes)</span></a></p><div><h3>Description</h3></div><div><input onblur='updateDescriptionAdd(this)' data-index='"+index+"' type='text' value='"+uploadedFile.description+"'></div><div><p style='color: white;'>The description may be used as the label of the link to the file</p></div></div>";

              // Create a checkbox for display
              if(uploadedFile.display){
                displayCell.innerHTML = '<input type="checkbox" checked  onclick="updateDisplayAdd(this)" data-index="'+index+'">';
              }else{
                displayCell.innerHTML = '<input type="checkbox"  onclick="updateDisplayAdd(this)" data-index="'+index+'">';
              }

              // Create a remove button
              operationsCell.innerHTML =
                '<button class="remove--btn" data-file-id="' +
                uploadedFile.id +
                '" onclick="removeFile(this)">Remove</button>';
            }
          });
        } else {
          alert("Error uploading file res: " + res.error);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Error uploading file: " + error.message);
      });
  }
}

/**
 * Removes a file from the WordPress Media Library by sending an AJAX request to remove it
 *
 * @param button - The button that was clicked
 *
 * @return { Promise } Resolves when the request has been
 */
function removeFile(button) {
  var fileId = button.getAttribute("data-file-id");
  var row = button.parentNode.parentNode;

  // Remove the row from the table
  row.parentNode.removeChild(row);

  // Remove the file from the uploadedFiles array
  uploadedFiles = uploadedFiles.filter(function (file) {
    return file.id !== parseInt(fileId);
  });

  // Send an AJAX request to remove the file from the WordPress Media Library
  var formData = new FormData();
  formData.append("action", "delete_file");
  formData.append("attachment_id", fileId);
  formData.append("security", my_ajax_obj.nonce);

  fetch(my_ajax_obj.ajax_url, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((res) => {
      // if the file is not found alert
      if (!res.success) {
        alert("Error deleting file: " + res.data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error deleting file: " + error.message);
    });
}

/**
 * Submits the form by submitting the CKEditor instance. This is a form submission that can be used to add or edit data in a form
 *
 * @param id - The ID of the form to
 */
function submitForm(id) {
// alert("Call");
  var inputs="";
var votechoice= document.querySelector('input[name="votingChoice"]:checked')
      .value;
      inputs = document.querySelectorAll(".cholce-box-inpt");

// if(votechoice=="punishment"){
//  // Corrected class name
//  inputs = document.querySelectorAll("#ballot-option-punishment .cholce-box-inpt");

// }
// else{
// // Corrected class name
//  inputs = document.querySelectorAll("#ballot-option-punishment .choice-box-inpt");


// }
console.log(inputs);
// return;
  // Initialize an array to store the input data
  var inputData = [];

  // Loop through each 'cholce-box-inpt' element
  inputs.forEach(function (input) {
    // Get the child input element
   
    var childInput = input.querySelector('input[type="text"]');

    // Get the ID and value of the child input element
    var inputId = childInput.id;
    var inputValue = childInput.value;
    console.log(inputValue);

    // Add the ID and value to the inputData array
    if (inputValue) {
      inputData.push({ id: inputId, text: inputValue });
    }
  });

  // Get the content from the CKEditor instance
  var editorContent = "";
  // This method is called when the CKEditor is initialized.
  //if (customEditor) {
    // Get the content of the CKEditor instance
   // var editorContent = customEditor.getData();
   var editorContent =CKEDITOR.instances['custom-editor'].getData()
    console.log("Editor content:", editorContent);

    // Continue with the form submission or other actions
 // } else {
  //  console.warn("CKEditor instance not initialized.");
    // Handle the scenario where CKEditor is not initialized
  //}
  console.log(editorContent);

  // Collect form data
  var formData = {
    id,
    proposalName: document.getElementById("proposal_name").value,
    remark: document.getElementById("add_comment").value,

    content: editorContent,
    votingOptions: inputData, // Assuming voting_options is a comma-separated string
    filesName: uploadedFiles, // Assuming filesName is an array of objects
    voteType: document.querySelector('input[name="votetype"]:checked').value,
    votingChoice: document.querySelector('input[name="votingChoice"]:checked')
      .value,
    votingStage: document.getElementById("votingStage").value,
    votingStage2: document.getElementById("votingstage2").value,

    proposedBy: document.getElementById("proposedBy").value,
    secondedBy: document.getElementById("secondedBy").value,
    createDate: document.getElementById("createDate").value,
    openingDate: document.getElementById("openingDate").value,
    closingDate: document.getElementById("closingDate").value,
    visibility: document.querySelector('input[name="visibility"]:checked')
      .value,
      blindVoting: document.querySelector('input[name="blindVoting"]:checked')
      .value,
    maximumChoices: document.getElementById("maximumChoices").value,
    activeStatus: document.querySelector('input[name="activeStatus"]:checked')
      .value,
     number_of_winner: document.getElementById("number_of_winner").value,
     withdrawntime: document.getElementById("withdrawntime").value,

action:'submit_voting_form'
  };
console.log(formData);
  const proposalName = document.getElementById("proposal_name").value;
  if (!proposalName) {
    alert("Please Enter Proposal Name");
    return;
  }
  if (!formData.blindVoting) {
    alert("Please select blind voting option");
    return;
  }

  // This function is used to check if the voting stage is draft.
  if (document.getElementById("votingStage").value !== "draft") {
    
    const resultType = document.querySelector('input[name="votingChoice"]:checked').value;
    const votingOptions = inputData;
    const proposedBy = document.getElementById("proposedBy").value;
    const secondedBy = document.getElementById("secondedBy").value;
    const createDate = document.getElementById("createDate").value;
    const openingDate = document.getElementById("openingDate").value;
    const closingDate = document.getElementById("closingDate").value;

    // if proposalName is not set alert
    
   
    // if resultType is not set to true alert
    if (!resultType) {
      alert("Please Enter Result type");
      return;
    }
    // if votingOptions is not set to true alert
    if (!votingOptions || votingOptions.length === 0) {
      alert("Please Enter Ballot Options");
      return;
    }
    // if proposedBy is true alert alert
    if (!proposedBy) {
      alert("Please Enter Proposed By");
      return;
    }
    // if not legal by user input
    // if (!secondedBy) {
    //   alert("Please Enter Seconded By");
    //   return;
    // }
    // if createDate is not set alert alert
    if (!createDate) {
      alert("Please Enter Create Date");
      return;
    }
    // if openingDate is not set alert alert
    // if (!openingDate) {
    //   alert("Please Enter Opening Date");
    //   return;
    // }

    if (document.getElementById("votingStage").value !== "autopass") {

      if (!openingDate) {
        alert("Please Enter Opening Date");
        return;
      }
    //    if (!secondedBy) {
    //   alert("Please Enter Seconded By");
    //   return;
    // }
    }
  
    // If closingDate is set to true alert alert
    if (!closingDate) {
      alert("Please Enter Closing Date");
      return;
    }
  }
  // console.log(formData);

  // Send data to server via AJAX
  var xhr = new XMLHttpRequest();
  // var ajaxUrl = "../wp-admin/admin-ajax.php";
  // xhr.open("POST", ajaxUrl + "?action=submit_voting_form");
  xhr.open("POST", my_ajax_obj.ajax_url + "?action=submit_voting_form");


  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onreadystatechange = function () {
    console.log("Ready state:", xhr.readyState);
    console.log("Status:", xhr.status);

    // Handle the form submission. If the form is not ready to be submitted the user will be redirected to the page.
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        // Handle success and redirect to the form
        if (response.success) {
          window.location.href = response.data.redirect_url;
        } else {
          // Handle error
          // console.error("Error: ", response.data);
          alert("Error submitting form: " + response.data);
        }
      } else {
        // Handle error
        console.error("Error submitting form. Status:", xhr.status);
      }
    }
  };
  xhr.send(JSON.stringify(formData));
}

/**
 * Adds an input to votingOptions and updates its text property if it doesn't exist
 *
 * @param id - The ID of the input to add to optionsArr
 * @param value - The value of the input to add to options
 */
function addingToOptions(id, value) {
  var inputs = document.querySelectorAll(".cholce-box-inpt");
//   var inputs="";
// var votechoice= document.querySelector('input[name="votingChoice"]:checked')
//       .value

// if(votechoice=="punishment"){
//    inputs = document.querySelectorAll("#ballot-option-punishment");
//    alert(inputs)
// }
// else{
//   inputs = document.querySelectorAll("#ballot-option-not-punishment");
//   alert(inputs)

// }

  // Initialize an array to store the input data
  var inputData = [];

  // Loop through each 'cholce-box-inpt' element
  inputs.forEach(function (input) {
    // Get the child input element
    var childInput = input.querySelector('input[type="text"]');

    // Get the ID and value of the child input element
    var inputId = childInput.id;
    var inputValue = childInput.value;

    // Add the ID and value to the inputData array
    inputData.push({ id: inputId, text: inputValue });
  });
}

/**
 * Submits a vote to the vote box. This function is called by the submit () function of the owbn - vote. js page
 *
 * @param id - The id of the owbn - vote. js page
 * @param userId - The user id of the vote. Must be unique for this vote to be added to the vote box.
 * @param userName - The user name of the vote. Must be unique for this vote to be added to the vote box.
 * @param votingChoice - The choice of vote to be added
 */
function submitVote(id, userId, userName, votingChoice) {
  // Prevent default form submission
  event.preventDefault();

  console.log(userName, "name");
  var voteOpinion;

  // Set the voteOpinion to the singleChoice or multipleChoices
  if (votingChoice == "single") {
    voteOpinion = singleChoice;
  } else {
    voteOpinion = multipleChoices;
  }

  var comment = document.getElementById("owbn-user-comment").value;

  // This method will check if any voteBox has the same userId and add a new voteBox object to the voteBox
  if (voteBox && voteBox.length > 0) {
    let userVoteFound = false;

    // Check if any object in voteBox has the same userId
    for (let i = 0; i < voteBox.length; i++) {
      if (voteBox[i].userId === userId) {
        // If found, update userVote and userComment
        voteBox[i].userVote = voteOpinion;
        voteBox[i].userName = userName;
        voteBox[i].userComment = comment;
        userVoteFound = true;
        voteBox[i].votetime=new Date().toLocaleString();

        break;
      }
    }

    // If no matching userId is found, add a new object to the array
    if (!userVoteFound) {
      voteBox.push({
        userId: userId,
        userName: userName,
        userVote: voteOpinion,
        userComment: comment,
        votetime:new Date().toLocaleString()

      });
    }
  } else {
    // If voteBox is empty or not defined, initialize it with an array containing the new object
    voteBox = [
      {
        userId: userId,
        userName: userName,
        userVote: voteOpinion,
        userComment: comment,
        votetime:new Date().toLocaleString()

      },
    ];
  }

  // console.log(voteBox); // For debugging purposes

  // Collect form data
  var formData = {
    voteId: id,
    voteBox: voteBox,
  };

  // console.log(formData);

  // Send data to server via AJAX
  var xhr = new XMLHttpRequest();
  // var ajaxUrl = "../wp-admin/admin-ajax.php";
  // xhr.open("POST", ajaxUrl + "?action=submit_voting_box");
  xhr.open("POST", my_ajax_obj.ajax_url + "?action=submit_voting_box");

  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onreadystatechange = function () {
    console.log("Ready state:", xhr.readyState);
    console.log("Status:", xhr.status);

    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          // Handle success and redirect
          console.log("Form submitted successfully");
          window.location.href = response.data.redirect_url;
        } else {
          // Handle error
          console.error("Error: ", response.data);
          alert("Error submitting form: " + response.data);
        }
      } else {
        // Handle error
        console.error("Error submitting form. Status:", xhr.status);
      }
    }
  };
  xhr.send(JSON.stringify(formData));
}


/* Update Description in object uploadedFiles*/
function updateDescriptionAdd(ele) {
  
  let index = ele.getAttribute('data-index'); // Get the index from the element
  let desc = ele.value; // Get the new description value

  // Ensure index is a number
  index = parseInt(index, 10);

  // Update the description in the uploadedFiles array
  if (uploadedFiles[index]) {
      uploadedFiles[index].description = desc; // Assuming each uploaded file object has a description property
      console.log("Updated uploadedFiles:", uploadedFiles); // Log the updated array for debugging
  } else {
      console.error("No uploaded file found at index:", index);
  }
}

/*Update Display in object uploadedFiles*/
function updateDisplayAdd(ele) {
  let index = ele.getAttribute('data-index'); // Get the index from the element
  let isChecked = ele.checked; // Check if the checkbox is checked

  // Ensure index is a number
  index = parseInt(index, 10);

  // Update the description in the uploadedFiles array based on checked state
  if (uploadedFiles[index]) {
      uploadedFiles[index].display = isChecked ? true : false; // Set description to true or false
  } else {
      console.error("No uploaded file found at index:", index);
  }
}

/**
 * Updates description of uploaded files. This is called on change of input field.
 *
 * @param input - jQuery object with input field'data - file - id
 */
function updateDescription(input) {
  var fileId = parseInt(input.getAttribute("data-file-id"));
  var description = input.value;

  uploadedFiles.forEach(function (file) {
    // Set file description if file. id is the file id.
    if (file.id === fileId) {
      file.description = description;
    }
  });

  console.log({ updatedFiles: uploadedFiles });
}

/**
 * Updates the display of uploaded files based on the value of the checkbox. This is used to determine whether or not the file should be displayed on the front - end
 *
 * @param checkbox - The checkbox that was
 */
function updateDisplay(checkbox) {
  var fileId = parseInt(checkbox.getAttribute("data-file-id"));
  var display = checkbox.checked;

  uploadedFiles.forEach(function (file) {
    // Set file display to display if file is not already in the file.
    if (file.id === fileId) {
      file.display = display;
    }
  });

  // console.log({ updatedFiles: uploadedFiles });
}
