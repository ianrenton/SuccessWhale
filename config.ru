# encoding: UTF-8
require './client'

use Rack::ShowExceptions

set :protection, :except => [:http_origin]

run Sinatra::Application
