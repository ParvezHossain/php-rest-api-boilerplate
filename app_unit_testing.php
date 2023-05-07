<?php
use PHPUnit\Framework\TestCase;
class RestApiTest extends TestCase {

  protected $apiUrl;

  protected function setUp() {
    $this->apiUrl = 'http://localhost/myapi';
  }

  protected function tearDown() {
    // Clean up after each test
  }

  public function testGetAllResources() {
    $url = $this->apiUrl . '/';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $resources = json_decode($response, true);
    $this->assertNotEmpty($resources);
    $this->assertInternalType('array', $resources);
  }

  public function testGetResourceById() {
    $url = $this->apiUrl . '/1';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $resource = json_decode($response, true);
    $this->assertNotEmpty($resource);
    $this->assertInternalType('array', $resource);
    $this->assertArrayHasKey('id', $resource);
    $this->assertEquals(1, $resource['id']);
  }

  public function testCreateResource() {
    $url = $this->apiUrl . '/';
    $data = [
      'name' => 'New Resource',
      'description' => 'This is a new resource'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('id', $result);
  }

  public function testUpdateResource() {
    $url = $this->apiUrl . '/1';
    $data = [
      'name' => 'Updated Resource',
      'description' => 'This resource has been updated'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
  }

  public function testDeleteResource() {
    $url = $this->apiUrl . '/1';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
  }

}
