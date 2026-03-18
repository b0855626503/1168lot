<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Step Form with Animation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .form-content {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .form-step {
            display: none;
            padding: 20px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-in-out;
        }

        .form-step.active {
            display: block;
            opacity: 1;
            transform: translateX(0);
        }

        .form-step.fade-out {
            opacity: 0;
            transform: translateX(-100%);
        }

        .form-header {
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.2rem;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f0f0f0;
        }

        .form-footer button {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-footer .prev-btn {
            background: #6c757d;
            color: white;
        }

        .form-footer .next-btn {
            background: #007bff;
            color: white;
        }

        .form-footer .submit-btn {
            background: #28a745;
            color: white;
            display: none;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        /* Responsive design */
        @media (max-width: 500px) {
            .form-content {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
@yield('content')

<script>
    const formSteps = document.querySelectorAll('.form-step');
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    const submitBtn = document.querySelector('.submit-btn');
    let currentStep = 0;

    function updateFormSteps(direction) {
        const activeStep = document.querySelector('.form-step.active');
        activeStep.classList.remove('active');

        if (direction === 'next') {
            activeStep.classList.add('fade-out');
            setTimeout(() => {
                activeStep.classList.remove('fade-out');
            }, 300);
        }

        formSteps[currentStep].classList.add('active');
        prevBtn.disabled = currentStep === 0;
        nextBtn.style.display = currentStep === formSteps.length - 1 ? 'none' : 'inline-block';
        submitBtn.style.display = currentStep === formSteps.length - 1 ? 'inline-block' : 'none';
    }

    nextBtn.addEventListener('click', () => {
        if (currentStep < formSteps.length - 1) {
            currentStep++;
            updateFormSteps('next');
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateFormSteps('prev');
        }
    });

    document.getElementById('multiStepForm').addEventListener('submit', (event) => {
        event.preventDefault();
        alert('Form submitted!');
    });
</script>
</body>
</html>