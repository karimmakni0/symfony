#!/bin/bash

# Download face-api.js models from official repository

# Set constants
MODEL_DIR="/Users/amir/Desktop/Pi/PiProject/public/models"
TEMP_DIR="/Users/amir/Desktop/Pi/PiProject/temp_models"
MODELS_REPO="https://github.com/justadudewhohacks/face-api.js-models/archive/refs/heads/master.zip"

# Create directories
mkdir -p "$MODEL_DIR"
mkdir -p "$TEMP_DIR"

# Clean existing models directory
rm -rf "$MODEL_DIR"/*

# Download models zip file
echo "Downloading models from repository..."
curl -L "$MODELS_REPO" -o "$TEMP_DIR/models.zip"

# Unzip the models
echo "Extracting models..."
unzip -q "$TEMP_DIR/models.zip" -d "$TEMP_DIR"

# Copy all models to the public directory
echo "Copying model files to public directory..."
cp -R "$TEMP_DIR"/face-api.js-models-master/* "$MODEL_DIR"

# Create symbolic links for backwards compatibility
cd "$MODEL_DIR"
ln -sf ssd_mobilenetv1_model-weights_manifest.json ssd_mobilenetv1_model-weights_manifest.json
ln -sf face_landmark_68_model-weights_manifest.json face_landmark_68_model-weights_manifest.json
ln -sf face_recognition_model-weights_manifest.json face_recognition_model-weights_manifest.json

# Clean up temporary files
echo "Cleaning up..."
rm -rf "$TEMP_DIR"

echo "Face API models installation complete!"
echo "Models are available at: $MODEL_DIR"
