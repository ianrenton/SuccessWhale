
# encoding: UTF-8
use Rack::Static,
  :urls => ["/img", "/js", "/css", "/fonts"],
  :root => "public"
  
headers = {
  'Content-Type'  => 'text/html',
  'Cache-Control' => 'public, max-age=86400'
}

run lambda { |env|
  [
    200, headers, File.open('public/client.html', File::RDONLY)
  ]
}

map "/login" do
  run lambda { |env|
  [
    200, headers, File.open('public/login.html', File::RDONLY)
  ]
}
end