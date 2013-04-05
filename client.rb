#!/usr/bin/env ruby
# encoding: UTF-8

# SuccessWhale's Web UI, powered by Sinatra
# Written by Ian Renton
# BSD licenced (See the LICENCE.md file)
# Homepage: https://github.com/ianrenton/successwhale

require 'rubygems'
require 'bundler/setup'
require 'unicorn'
require 'sinatra'
require 'rack/throttle'
require 'net/http'

API_SERVER = 'http://api.successwhale.com/v3'

# Throttle clients to max. 1000 requests per hour
use Rack::Throttle::Hourly,   :max => 1000

# Enable sessions so that we can store the user's authentication in a cookie
enable :sessions

# Serve default page
get '/' do
  send_file File.join(settings.public_folder, 'index.html')
end

# API proxy
get '/apiproxy/*' do |path|
  uri = URI.parse("#{API_SERVER}/#{path}")
  uri.query = URI.encode_www_form( params )
  res = Net::HTTP.get_response(uri)
  status res.code
  content_type res['content-type']
  res.body
end

post '/apiproxy/*' do |path|
  uri = URI.parse("#{API_SERVER}/#{path}")
  res = Net::HTTP.post_form(uri, params)
  status res.code
  content_type res['content-type']
  res.body
end

delete '/apiproxy/*' do |path|
  uri = URI.parse("#{API_SERVER}/#{path}")
  uri.query = URI.encode_www_form( params )
  http = Net::HTTP.new(uri.host, uri.port)
  res = http.request(Net::HTTP::Delete.new(uri.request_uri))
  status res.code
  content_type res['content-type']
  res.body
end

# 404
not_found do
  status 404
  '<h1>SuccessWhale - Page not found</h3>'
end
