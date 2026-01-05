#!/bin/bash

# YogaAI Demo UI Quick Start Script

echo "🧘 YogaAI Demo UI Setup"
echo "======================="
echo ""

# Check if we're in the right directory
if [ ! -d "demo-ui" ]; then
    echo "❌ Error: demo-ui directory not found"
    echo "Please run this script from the project root"
    exit 1
fi

cd demo-ui

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
    echo ""
fi

echo "✅ Setup complete!"
echo ""
echo "🚀 Starting development server..."
echo ""
echo "📝 Demo Login Credentials:"
echo "   Email: sarah.johnson@example.com"
echo "   Password: password123"
echo ""
echo "🌐 Opening http://localhost:3000"
echo ""

# Start the dev server
npm run dev
