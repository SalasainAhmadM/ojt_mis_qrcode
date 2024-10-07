const signupBtn = document.getElementById("signup-btn");
const signinBtn = document.getElementById("signin-btn");
const mainContainer = document.querySelector(".container");

signupBtn.addEventListener("click", () => {
  mainContainer.classList.toggle("change");
});
signinBtn.addEventListener("click", () => {
  mainContainer.classList.toggle("change");
});

document.addEventListener('DOMContentLoaded', function () {
  const roleCheckboxes = document.querySelectorAll('.role-checkbox');
  const roleFields = document.querySelectorAll('.role-fields');

  roleCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function () {
      // Hide all role fields initially
      roleFields.forEach(field => field.style.display = 'none');
      
      // Uncheck all other checkboxes
      roleCheckboxes.forEach(cb => {
        if (cb !== checkbox) {
          cb.checked = false;
        }
      });

      // Show the selected role fields
      if (checkbox.checked) {
        document.getElementById(checkbox.value + '-fields').style.display = 'flex';
      }
    });
  });

  // Toggle password visibility
  const togglePasswordButtons = document.querySelectorAll('.toggle-password');

  togglePasswordButtons.forEach(button => {
    button.addEventListener('click', function () {
      const input = this.previousElementSibling;
      const icon = this.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });
});

// const signupBtn = document.getElementById("signup-btn");
// const signinBtn = document.getElementById("signin-btn");
// const mainContainer = document.querySelector(".container");

// signupBtn.addEventListener("click", () => {
//   mainContainer.classList.toggle("change");
// });
// signinBtn.addEventListener("click", () => {
//   mainContainer.classList.toggle("change");
// });

// document.addEventListener('DOMContentLoaded', function () {
//   const roleCheckboxes = document.querySelectorAll('.role-checkbox');
//   const roleFields = document.querySelectorAll('.role-fields');

//   roleCheckboxes.forEach(checkbox => {
//     checkbox.addEventListener('change', function () {
//       // Hide all role fields initially
//       roleFields.forEach(field => field.style.display = 'none');
      
//       // Uncheck all other checkboxes
//       roleCheckboxes.forEach(cb => {
//         if (cb !== checkbox) {
//           cb.checked = false;
//         }
//       });

//       // Show the selected role fields
//       if (checkbox.checked) {
//         document.getElementById(checkbox.value + '-fields').style.display = 'flex';
//       }
//     });
//   });

//   // Toggle password visibility
//   const togglePasswordButtons = document.querySelectorAll('.toggle-password');

//   togglePasswordButtons.forEach(button => {
//     button.addEventListener('click', function () {
//       const input = this.previousElementSibling;
//       const icon = this.querySelector('i');
      
//       if (input.type === 'password') {
//         input.type = 'text';
//         icon.classList.remove('fa-eye');
//         icon.classList.add('fa-eye-slash');
//       } else {
//         input.type = 'password';
//         icon.classList.remove('fa-eye-slash');
//         icon.classList.add('fa-eye');
//       }
//     });
//   });
// });