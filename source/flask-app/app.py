#!/usr/bin/env python3
from flask import Flask, jsonify, Response
import subprocess
import os
import tempfile
from datetime import datetime

app = Flask(__name__)

KEYS_DIR = '/var/www/html/keys'
PRIVATE_KEY_FILE = os.path.join(KEYS_DIR, 'repo-signing.key')

@app.route('/sitemap.xml', methods=['GET'])
def sitemap():
    """
    Sitemap showing all available endpoints
    """
    xml = '''<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>http://localhost:5000/sitemap.xml</loc>
        <lastmod>{timestamp}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>1.0</priority>
        <description>Sitemap of all endpoints</description>
    </url>
    <url>
        <loc>http://localhost:5000/keys/private</loc>
        <lastmod>{timestamp}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.8</priority>
        <description>Get private signing key (content and path)</description>
    </url>
    <url>
        <loc>http://localhost:5000/install</loc>
        <lastmod>{timestamp}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
        <description>Install internal package via pip</description>
    </url>
</urlset>'''.format(timestamp=datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'))
    
    return Response(xml, mimetype='application/xml')


@app.route('/keys/private', methods=['GET'])
def get_private_key():
    """
    Return private key content and path
    No authentication (internal service only)
    """
    try:
        if not os.path.exists(PRIVATE_KEY_FILE):
            return jsonify({
                'success': False,
                'error': f'Private key not found at {PRIVATE_KEY_FILE}'
            }), 404
        
        with open(PRIVATE_KEY_FILE, 'r') as f:
            key_content = f.read()
        
        return jsonify({
            'success': True,
            'path': PRIVATE_KEY_FILE,
            'content': key_content
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.errorhandler(404)
def not_found(e):
    return jsonify({
        'success': False,
        'error': 'Endpoint not found',
        'available_endpoints': ['/sitemap.xml', '/keys/private', '/install']
    }), 404


@app.errorhandler(500)
def internal_error(e):
    return jsonify({
        'success': False,
        'error': 'Internal server error'
    }), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
