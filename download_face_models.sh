#!/bin/bash

# Change to the models directory
cd /Users/amir/Desktop/Pi/PiProject/public/models

# Create directories for each model type
mkdir -p ssd_mobilenetv1 tiny_face_detector mtcnn tiny_yolov2 face_recognition face_landmark_68 face_expression_recognition age_gender_recognition

# Download SSD MobileNet v1 model
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/ssd_mobilenetv1_model-weights_manifest.json?raw=true -o ssd_mobilenetv1_model-weights_manifest.json
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/ssd_mobilenetv1_model.bin?raw=true -o ssd_mobilenetv1_model.bin

# Download face landmark model
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/face_landmark_68_model-weights_manifest.json?raw=true -o face_landmark_68_model-weights_manifest.json
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/face_landmark_68_model.bin?raw=true -o face_landmark_68_model.bin

# Download face recognition model
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/face_recognition_model-weights_manifest.json?raw=true -o face_recognition_model-weights_manifest.json
curl -L https://github.com/justadudewhohacks/face-api.js/blob/master/weights/face_recognition_model.bin?raw=true -o face_recognition_model.bin

echo "All face-api.js model files have been downloaded!"
