<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>table</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" integrity="sha512-72OVeAaPeV8n3BdZj7hOkaPSEk/uwpDkaGyP4W2jSzAC8tfiO4LMEDWoL3uFp5mcZu+8Eehb4GhZWFwvrss69Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .heading-box{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px, rgba(10, 37, 64, 0.35) 0px -2px 6px 0px inset;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: clamp(1.2rem, 2rem + 2vw, 3rem);
            background: linear-gradient(90deg, #D10CE8, #1CB5E0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .list-details p{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px, rgba(10, 37, 64, 0.35) 0px -2px 6px 0px inset;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: clamp(1.2rem, 2rem + 2vw, 3rem);
            background: linear-gradient(90deg, #D10CE8, #1CB5E0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: 1s all ease-in-out;
            cursor: pointer;
            &:hover{
                transition: .3s all ease-in-out;
                transform: scale(.97);
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-sm-4">
                <div class="heading-box mb-5 py-4">
                    No
                </div>
                @foreach ($patientQueue as $queue)
                  <div class="list-details">
                    <p class="mb-4 py-3">{{ $queue->no }}</p>
                  </div>
                @endforeach
            </div>
            <div class="col-sm-4">
                <div class="heading-box mb-5 py-4">
                   Patient
                </div>
                @foreach ($patientQueue as $queue)
                  <div class="list-details">
                    <p class="mb-4 py-3">{{ $queue->appointment->patient->patientUser->full_name }}</p>
                  </div>
                @endforeach
            </div>
            <div class="col-sm-4">
                <div class="heading-box mb-5 py-4">
                   Doctor
                </div>
                @foreach ($patientQueue as $queue)
                  <div class="list-details">
                    <p class="mb-4 py-3">{{ $queue->appointment->doctor->doctorUser->full_name }}</p>
                  </div>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>